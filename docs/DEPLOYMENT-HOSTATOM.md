# คู่มือ Deploy RETC-CTS บน HostAtom (Plesk)

เอกสารนี้คือ "Deployment Checklist" ที่ถูกอ้างถึงใน [.gitignore](../.gitignore),
[routes/console.php](../routes/console.php), [deploy/supervisor-retc-cts-worker.conf](../deploy/supervisor-retc-cts-worker.conf)
และ [.env.production.example](../.env.production.example)

## วิธี deploy ของโปรเจกต์นี้

HostAtom ใช้ Plesk ซึ่งดึงโค้ดจาก GitHub แบบ **pull-only** (เซิร์ฟเวอร์ไม่ push กลับ)
งาน build ทั้งหมด — `composer install`, `npm run build`, `artisan optimize` — รันบน
เซิร์ฟเวอร์ผ่าน **Additional deployment actions** ของ Plesk

```
เครื่อง dev ──git push──> GitHub ──Plesk pull──> HostAtom ──deploy actions──> เว็บพร้อมใช้
```

> **สำคัญ:** `public/build` ถูก gitignore ตั้งแต่ commit `df62a32` เป็นต้นไป
> ถ้า deploy action ไม่รัน `npm run build` เซิร์ฟเวอร์จะไม่มี `manifest.json`
> แล้ว **ทุกหน้าจะ 500** ไม่ใช่แค่หน้าตาเพี้ยน

---

## ส่วนที่ 1 — ติดตั้งครั้งแรก (ทำครั้งเดียว)

### 1.1 เตรียม hosting

| รายการ | ค่าที่ต้องใช้ |
| --- | --- |
| PHP | 8.2 ขึ้นไป (ตาม `composer.json`) |
| Document root | ต้องชี้ไปที่โฟลเดอร์ **`public`** ของโปรเจกต์ ไม่ใช่ root ของ repo |
| PHP extensions | ตามมาตรฐาน Laravel — `pdo_mysql`, `mbstring`, `openssl`, `gd`/`zip` (สำหรับ Excel/PDF export) |
| SSH access | ต้องเปิด (Plesk → Web Hosting Access → Access to the server over SSH) |
| Node.js | ต้องมี — ใช้ build frontend |

### 1.2 สร้างฐานข้อมูล

Plesk → Databases → Add Database
สร้าง database + user แยกสำหรับ production (เช่น `retc_cts_prod`) เก็บรหัสผ่านไว้ใส่ `.env`

### 1.3 ตั้ง Git integration

Plesk → Websites & Domains → **Git** → Add Repository

- Remote Git repository: `https://github.com/rattanasakking/retc-cts.git`
- Branch: `main`
- Deployment mode: **Automatic** (deploy ทันทีที่ push) หรือ **Manual** (กด Pull Now เอง)

### 1.4 ตั้ง Additional deployment actions

ในหน้า Git ของ Plesk เปิด **Enable additional deployment actions** แล้วใส่:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

**ถ้า `npm` / `composer` ไม่อยู่ใน PATH** (พบบ่อยบน Plesk) ให้หา path จริงก่อน แล้วใส่แบบเต็ม:

```bash
which npm composer php || ls /opt/plesk/node/*/bin/npm
# ตัวอย่างเมื่อต้องใช้ full path
/opt/plesk/node/22/bin/npm ci
/opt/plesk/node/22/bin/npm run build
```

### 1.5 ตั้ง .env

```bash
cd ~/httpdocs                       # หรือ path จริงของ repo
cp .env.production.example .env
nano .env                           # กรอก DB_*, APP_URL, MAIL_*, LINE_CHANNEL_ACCESS_TOKEN
php artisan key:generate
```

จุดที่คนลืมบ่อย:

- `APP_ENV=production` และ `APP_DEBUG=false` — ถ้าเผลอเปิด debug จะโชว์ค่าใน `.env` ตอน error
- `APP_URL=https://...` ให้ตรงกับโดเมนจริง (มีผลกับลิงก์ในอีเมลและ asset)
- `SESSION_SECURE_COOKIE=true` เมื่อใช้ https
- `MYSQLDUMP_PATH` / `MYSQL_CLI_PATH` — บน Linux ปกติแค่ `mysqldump` / `mysql` เฉยๆ ก็ใช้ได้
  (ตรวจด้วย `which mysqldump && which mysql`) ฟีเจอร์ Settings → สำรอง/กู้คืนข้อมูล พึ่งค่านี้
- `TRUSTED_PROXIES` — ใส่เฉพาะเมื่อมี Cloudflare/proxy หน้าเว็บ ถ้าไม่มีให้เว้นว่าง

### 1.6 รัน setup ครั้งแรก

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=ThaiGeographySeeder --force
php artisan storage:link
php artisan optimize
```

> ⚠️ **ห้ามรัน `php artisan db:seed` เปล่าๆ บน production** — `DatabaseSeeder` สร้าง
> บัญชีทดสอบ (`admin@retc-cts.test` ฯลฯ) และข้อมูลนักศึกษาปลอม
> รันเฉพาะ `ThaiGeographySeeder` ซึ่งเป็นข้อมูลจังหวัด/อำเภอ/ตำบลที่ระบบต้องใช้จริง
> (เขียนแบบ upsert รันซ้ำได้ไม่พัง)

### 1.7 สิทธิ์ไฟล์

```bash
chmod -R 775 storage bootstrap/cache
```

### 1.8 สร้างบัญชีผู้ดูแลระบบตัวจริง

```bash
php artisan tinker
```
```php
\App\Models\User::create([
    'name' => 'ผู้ดูแลระบบ',
    'email' => 'admin@โดเมนจริง',
    'password' => 'รหัสผ่านที่ตั้งเอง',   // cast 'hashed' จะ hash ให้อัตโนมัติ
    'role' => \App\Enums\UserRole::Admin,
]);
```

### 1.9 ตั้ง cron

Plesk → Tools & Settings → **Scheduled Tasks**

| ความถี่ | คำสั่ง | ทำอะไร |
| --- | --- | --- |
| ทุก 1 นาที | `php /path/to/retc-cts/artisan schedule:run` | ตัวขับ scheduler ทั้งหมดใน `routes/console.php` (สำรอง DB ตี 2, ล้าง session หมดอายุ) |
| ทุก 5 นาที | `php /path/to/retc-cts/artisan queue:work --stop-when-empty --tries=3` | ระบายคิว (นำเข้า CSV, แจ้งเตือน) — ใช้แทน supervisor บน shared hosting |

ถ้าเป็น VPS ที่มี root ให้ใช้ [deploy/supervisor-retc-cts-worker.conf](../deploy/supervisor-retc-cts-worker.conf) แทน cron ตัวที่สอง

### 1.10 ฟอนต์ไทยสำหรับ PDF export

dompdf ไม่มีฟอนต์ไทยมาให้ และ `storage/fonts` ถูก gitignore จึงต้องลงบนเซิร์ฟเวอร์เอง
อัปโหลดฟอนต์ที่มีสิทธิ์ใช้งานถูกต้อง (เช่น TH Sarabun New หรือ Noto Sans Thai)
โดยตั้งชื่อไฟล์เป็น `tahoma.ttf` และ `tahomabd.ttf` แล้วสั่ง:

```bash
php artisan app:register-pdf-thai-font --source=/path/to/fonts
```

ไม่ทำขั้นนี้ ระบบยังใช้งานได้ปกติ แต่ PDF ที่ export จะไม่แสดงตัวอักษรไทย

---

## ส่วนที่ 2 — Deploy รอบถัดไป (ทำทุกครั้งที่มีของใหม่)

**บนเครื่อง dev**
```bash
php artisan test          # ต้องเขียว
git push origin main
```

**บนเซิร์ฟเวอร์**
1. Plesk → Git → **Pull Now** (ข้ามได้ถ้าตั้ง Automatic)
2. deploy actions จะรันเองตามที่ตั้งไว้ข้อ 1.4 — `composer install` → `npm run build` → `migrate --force` → `optimize`
3. เปิดเว็บเช็กหน้าที่เพิ่ง deploy

**เช็กว่าจริงหรือเปล่า** ถ้า deploy actions ไม่ทำงาน ให้ SSH เข้าไปรันมือ:
```bash
cd ~/httpdocs
git log --oneline -1          # commit ตรงกับที่ push ไหม
php artisan optimize:clear && php artisan optimize
```

`php artisan optimize` สำคัญทุกรอบที่มี **route ใหม่** หรือ **แก้ Blade** — route cache
เก่าจะทำให้หน้าใหม่ 404 และ view cache เก่าจะทำให้เมนูไม่อัปเดต

---

## ส่วนที่ 3 — รอบนี้โดยเฉพาะ (commit `df62a32`)

commit นี้เปลี่ยนวิธี build จาก "commit ไฟล์ build ขึ้น git" เป็น "build บนเซิร์ฟเวอร์"
ตอน pull มันจะ **ลบ** `public/build` ทิ้งจากเซิร์ฟเวอร์ ต้องทำตามลำดับนี้เท่านั้น:

1. ตั้ง deploy actions ตามข้อ 1.4 ให้เสร็จ **ก่อน** (โดยเฉพาะ `npm ci && npm run build`)
2. ทดสอบว่า npm ใช้ได้จริง: `cd ~/httpdocs && npm ci && npm run build` — ต้องได้ไฟล์ใน `public/build/`
3. ค่อย push / กด Pull Now
4. เปิด `https://โดเมน/students/recently-updated` เช็กว่าหน้าใหม่ทำงาน และหน้าอื่นยังมี CSS ปกติ

ถ้าเซิร์ฟเวอร์ยังมี `public/build` เวอร์ชันเก่าค้างเป็น local changes แล้ว pull ไม่ผ่าน:
```bash
git checkout -- public/build && git pull
```

---

## ส่วนที่ 4 — ย้อนกลับเมื่อพัง

| สถานการณ์ | วิธีแก้ |
| --- | --- |
| `npm run build` ล้ม / RAM ไม่พอ | `git revert df62a32` แล้ว push — ไฟล์ build ชุดที่ commit ไว้จะกลับมาใน git เหมือนเดิม |
| โค้ดรอบล่าสุดมีปัญหา | `git revert <commit>` บนเครื่อง dev แล้ว push ใหม่ (อย่าแก้ไฟล์บนเซิร์ฟเวอร์ตรงๆ เพราะ pull รอบหน้าจะชน) |
| migration ใหม่ทำข้อมูลเสีย | กู้จาก Settings → สำรอง/กู้คืนข้อมูล (มี backup อัตโนมัติทุกวันตี 2 เก็บ 30 ชุด) |

---

## ส่วนที่ 5 — อาการที่เจอบ่อย

| อาการ | สาเหตุ | แก้ |
| --- | --- | --- |
| หน้าใหม่ขึ้น 404 ทั้งที่โค้ดขึ้นแล้ว | route cache เก่า | `php artisan optimize:clear && php artisan optimize` |
| ทุกหน้า 500 `Unable to locate file in Vite manifest` | ยังไม่ได้ `npm run build` บนเซิร์ฟเวอร์ | `npm ci && npm run build` |
| เว็บไม่มี CSS เลย | document root ไม่ได้ชี้ที่ `public/` หรือ build หาย | ตรวจ document root / rebuild |
| ล็อกอินแล้วเด้งกลับหน้า login | session cookie ไม่ถูกส่งกลับ — เช็ก `SESSION_SECURE_COOKIE` กับ `APP_URL` ว่าตรง scheme กัน | แก้ `.env` แล้ว `php artisan optimize:clear` |
| นำเข้า CSV ค้าง ไม่มีอะไรเกิดขึ้น | ไม่มีตัวรันคิว | ตั้ง cron `queue:work --stop-when-empty` ตามข้อ 1.9 |
| PDF export ตัวหนังสือไทยหาย | ยังไม่ได้ลงฟอนต์ | ทำตามข้อ 1.10 |
| แผนที่/ตัวเลือกจังหวัดว่างเปล่า | ยังไม่ได้ seed ข้อมูลภูมิศาสตร์ | `php artisan db:seed --class=ThaiGeographySeeder --force` |
| สำรองข้อมูลใน Settings ล้มเหลว | `MYSQLDUMP_PATH` / `MYSQL_CLI_PATH` ผิด | `which mysqldump` แล้วแก้ `.env` |
