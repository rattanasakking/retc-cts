# คู่มือ Deploy RETC-CTS บน HostAtom (Plesk)

เอกสารนี้คือ "Deployment Checklist" ที่ถูกอ้างถึงใน [.gitignore](../.gitignore),
[routes/console.php](../routes/console.php), [deploy/supervisor-retc-cts-worker.conf](../deploy/supervisor-retc-cts-worker.conf)
และ [.env.production.example](../.env.production.example)

## วิธี deploy ของโปรเจกต์นี้

HostAtom ใช้ Plesk ซึ่งดึงโค้ดจาก GitHub แบบ **pull-only** (เซิร์ฟเวอร์ไม่ push กลับ)
frontend ถูก build จากเครื่อง dev แล้ว commit ขึ้น git ไปพร้อมโค้ด — เซิร์ฟเวอร์
ไม่ต้องรัน Node เลย

```
เครื่อง dev ──build + push──> GitHub ──Plesk pull──> เคลียร์ cache ──> เว็บพร้อมใช้
```

> **กฎเหล็กของฝั่ง dev:** แก้อะไรที่กระทบ Blade / CSS / JS ต้อง `npm run build`
> แล้ว commit `public/build` ไปด้วยเสมอ ไม่งั้นเซิร์ฟเวอร์จะได้ CSS เก่าแบบเงียบ ๆ

เซิร์ฟเวอร์มี Node.js 22 ติดตั้งอยู่ (Plesk → Node.js) ใช้ build ได้ถ้าจำเป็น แต่ไม่ได้ใช้
ในกระบวนการปกติ — เคยลองย้ายไป build ที่นั่นแล้วถอยกลับ เพราะ deploy ทำด้วยมือผ่านหน้าจอ
Plesk ทำให้เว็บ 500 ตลอดช่วงระหว่างกด Pull Now กับ build เสร็จ

> **ถ้าวันหนึ่งตั้ง Additional deployment actions ได้** ให้ย้ายไป build บนเซิร์ฟเวอร์
> จะดีกว่า (คลิกเดียวจบ ไม่มีทางลืม rebuild) — ตอนนั้นค่อยเพิ่ม `/public/build` กลับเข้า
> `.gitignore` แล้วใส่ `npm ci --include=dev` + `npm run build` ใน deployment actions

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

### 1.4 ตั้ง Additional deployment actions (ถ้าใช้ได้)

ถ้า Plesk เปิดให้ใช้ ในหน้า Git กด **Enable additional deployment actions** แล้วใส่:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

ไม่มี `npm` ในนี้ เพราะไฟล์ frontend ที่ build แล้วมาพร้อม git อยู่แล้ว

ถ้า Plesk ไม่ยอมให้ใช้ deployment actions ก็ข้ามได้ แค่ต้องรันคำสั่งพวกนี้เองหลัง pull
(ดูข้อ 2 และ 2.1)

**ถ้า `composer` / `php` ไม่อยู่ใน PATH** (พบบ่อยบน Plesk) ให้หา path จริงก่อนแล้วใส่แบบเต็ม:

```bash
which composer php
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
npm run build             # ทุกครั้งที่แก้ Blade / CSS / JS — ห้ามลืม
git add -A && git commit && git push origin main
```

**บนเซิร์ฟเวอร์**
1. Plesk → Git → **Pull Now** (ข้ามได้ถ้าตั้ง Automatic)
2. ถ้าตั้ง deploy actions ไว้ (ข้อ 1.4) มันรันเองจบ — ถ้าไม่ได้ตั้ง ทำข้อ 2.1
3. เปิดเว็บเช็กหน้าที่เพิ่ง deploy

`php artisan optimize` สำคัญทุกรอบที่มี **route ใหม่** หรือ **แก้ Blade** — route cache
เก่าจะทำให้หน้าใหม่ 404 และ view cache เก่าจะทำให้เมนูไม่อัปเดต

### 2.1 เคลียร์ cache เมื่อไม่มี deploy actions

**ถ้ามี SSH**
```bash
cd ~/httpdocs
git log --oneline -1          # commit ตรงกับที่ push ไหม
php artisan optimize:clear && php artisan optimize
```

**ถ้าไม่มี SSH** — Plesk → Scheduled Tasks → Add Task → Run a PHP script
- Script path: `httpdocs/artisan`, arguments: `optimize:clear` → กด Run Now
- แก้ arguments เป็น `optimize` → กด Run Now อีกครั้ง แล้วลบ task ทิ้ง

**ถ้าเข้าไม่ได้ทั้งสองทาง** — Files → File Manager ลบไฟล์พวกนี้ทิ้ง ได้ผลเหมือนกัน
(เว็บจะช้าลงนิดหน่อยเพราะไม่มี cache แต่ทำงานถูกต้อง)
`bootstrap/cache/routes-v7.php`, `bootstrap/cache/config.php`, และไฟล์ทั้งหมดใน `storage/framework/views/`

---

## ส่วนที่ 3 — เรื่องที่เคยลองแล้วถอย: build บนเซิร์ฟเวอร์

เซิร์ฟเวอร์มี Node.js 22 (Plesk → Node.js → Run Node.js commands) เคยย้ายไป build ที่นั่น
ใน commit `df62a32` แล้วถอยกลับในรอบถัดมา เพราะ:

- deploy ทำด้วยมือผ่าน Plesk UI — ต้องเปิด 3 หน้าจอต่อหนึ่ง deploy
- ระหว่างกด Pull Now กับ `npm run build` เสร็จ เว็บ **500 ทุกหน้า** เพราะไม่มี `manifest.json`
- `vite` อยู่ใน devDependencies ต้อง `npm ci --include=dev` ก่อน ไม่งั้นได้ `vite: not found`
- ช่อง Run Node.js commands รันได้แค่คำสั่ง npm — `php artisan` ต้องไปทำที่อื่นอยู่ดี

**ถ้าจะย้ายกลับไป build บนเซิร์ฟเวอร์** ให้ทำตามลำดับนี้เท่านั้น
1. ตั้ง Additional deployment actions ให้รัน `npm ci --include=dev && npm run build && php artisan optimize` ให้ได้ก่อน
2. ทดสอบ `npm ci --include=dev` + `npm run build` บนเซิร์ฟเวอร์ว่าผ่านจริง
3. ค่อยเพิ่ม `/public/build` เข้า `.gitignore` แล้ว `git rm -r --cached public/build`

---

## ส่วนที่ 4 — ย้อนกลับเมื่อพัง

| สถานการณ์ | วิธีแก้ |
| --- | --- |
| โค้ดรอบล่าสุดมีปัญหา | `git revert <commit>` บนเครื่อง dev แล้ว push ใหม่ (อย่าแก้ไฟล์บนเซิร์ฟเวอร์ตรงๆ เพราะ pull รอบหน้าจะชน) |
| migration ใหม่ทำข้อมูลเสีย | กู้จาก Settings → สำรอง/กู้คืนข้อมูล (มี backup อัตโนมัติทุกวันตี 2 เก็บ 30 ชุด) |

---

## ส่วนที่ 5 — อาการที่เจอบ่อย

| อาการ | สาเหตุ | แก้ |
| --- | --- | --- |
| หน้าใหม่ขึ้น 404 ทั้งที่โค้ดขึ้นแล้ว | route cache เก่า | `php artisan optimize:clear && php artisan optimize` |
| ทุกหน้า 500 `Unable to locate file in Vite manifest` | `public/build` หายจากเซิร์ฟเวอร์ | เช็กว่า `public/build/manifest.json` มีอยู่จริง ถ้าไม่มีให้ `npm run build` บนเครื่อง dev แล้ว commit + push ใหม่ |
| เว็บไม่มี CSS เลย | document root ไม่ได้ชี้ที่ `public/` | ตรวจ document root |
| หน้าตาเพี้ยน สไตล์ใหม่ไม่มา | ลืม `npm run build` ก่อน commit | build แล้ว commit `public/build` ใหม่ |
| ล็อกอินแล้วเด้งกลับหน้า login | session cookie ไม่ถูกส่งกลับ — เช็ก `SESSION_SECURE_COOKIE` กับ `APP_URL` ว่าตรง scheme กัน | แก้ `.env` แล้ว `php artisan optimize:clear` |
| นำเข้า CSV ค้าง ไม่มีอะไรเกิดขึ้น | ไม่มีตัวรันคิว | ตั้ง cron `queue:work --stop-when-empty` ตามข้อ 1.9 |
| PDF export ตัวหนังสือไทยหาย | ยังไม่ได้ลงฟอนต์ | ทำตามข้อ 1.10 |
| แผนที่/ตัวเลือกจังหวัดว่างเปล่า | ยังไม่ได้ seed ข้อมูลภูมิศาสตร์ | `php artisan db:seed --class=ThaiGeographySeeder --force` |
| สำรองข้อมูลใน Settings ล้มเหลว | `MYSQLDUMP_PATH` / `MYSQL_CLI_PATH` ผิด | `which mysqldump` แล้วแก้ `.env` |
