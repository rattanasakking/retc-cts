# คู่มือติดตั้ง RETC-CTS สำหรับสถานศึกษาอื่น

ระบบนี้ออกแบบให้วิทยาลัยอื่นนำไปติดตั้งเป็นของตัวเองได้ **ชื่อ โลโก้ สี และข้อมูลติดต่อ
ตั้งค่าได้จากหน้าเว็บ** ไม่ต้องแก้โค้ด

> ติดตั้ง**แยกชุดต่อวิทยาลัย** (คนละโดเมน คนละฐานข้อมูล) ไม่ใช่ชุดเดียวใช้ร่วมกัน —
> ข้อมูลนักศึกษาไม่ปนกัน รับผิดชอบตาม PDPA ได้ชัดเจน และที่หนึ่งพังไม่กระทบที่อื่น

---

## 1. ตรวจสอบสเปกเซิร์ฟเวอร์

| รายการ | ต้องการ | ตรวจด้วย |
| --- | --- | --- |
| PHP | 8.2 ขึ้นไป | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL / MariaDB | 8.0 / 10.6 ขึ้นไป | `mysql -V` |
| PHP extensions | `pdo_mysql` `mbstring` `openssl` `fileinfo` `dom` `xml` `zip` `gd` `curl` | `php -m` |
| memory_limit | 256M ขึ้นไป | `php -i \| grep memory_limit` |
| max_execution_time | 120 วินาทีขึ้นไป | จำเป็นตอนนำเข้า CSV จำนวนมาก |
| Document root | ชี้ที่โฟลเดอร์ `public/` ได้ | ตั้งในแผงควบคุมโฮสต์ |
| Node.js | 20.19+ หรือ 22.12+ | **ไม่จำเป็น** — ไฟล์ frontend ที่ build แล้วมากับ git |

ถ้าโฮสต์เป็นแบบ **shared hosting ที่ไม่มี SSH** ให้อ่าน [ข้อ 7](#7-กรณีโฮสต์ที่ไม่มี-ssh) ก่อน

---

## 2. ดึงโค้ดจาก GitHub

```bash
cd /path/to/webroot
git clone https://github.com/rattanasakking/retc-cts.git
cd retc-cts
composer install --no-dev --optimize-autoloader
```

ถ้าโฮสต์รัน `composer` ไม่ได้ ให้ `composer install --no-dev --optimize-autoloader` บนเครื่องอื่นก่อน
แล้วอัปโหลด**ทั้งโปรเจกต์รวมโฟลเดอร์ `vendor/`** ขึ้นไป

---

## 3. สร้างฐานข้อมูลและตั้งค่า .env

สร้าง MySQL database + user ในแผงควบคุมโฮสต์ แล้ว

```bash
cp .env.production.example .env
nano .env
```

ค่าที่ต้องกรอกและมักพลาดกัน

| ตัวแปร | หมายเหตุ |
| --- | --- |
| `APP_URL` | โดเมนจริง และต้องเป็น `https://` ถ้ามี SSL |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` — ถ้าเผลอเปิด debug ค่าใน .env จะโผล่บนหน้า error |
| `DB_*` | ตามที่สร้างไว้ |
| `SESSION_SECURE_COOKIE` | `true` เมื่อใช้ https ไม่งั้นล็อกอินแล้วเด้งกลับ |
| `MAIL_*` | สำหรับอีเมลแจ้งเตือน |
| `LINE_CHANNEL_ACCESS_TOKEN` | ของ LINE OA วิทยาลัยนั้น (เว้นว่างได้ถ้ายังไม่ใช้) |
| `MYSQLDUMP_PATH` / `MYSQL_CLI_PATH` | บน Linux ปกติใส่ `mysqldump` / `mysql` เฉย ๆ — ใช้กับฟีเจอร์สำรองข้อมูล |

---

## 4. ติดตั้งด้วยคำสั่งเดียว

```bash
php artisan key:generate
php artisan app:install
```

`app:install` จะทำให้ทั้งหมดนี้ตามลำดับที่ถูกต้อง

1. สร้างตารางฐานข้อมูล
2. นำเข้าข้อมูล **จังหวัด / อำเภอ / ตำบล** ทั่วประเทศ (จำเป็นต่อการเลือกที่ตั้งสถานประกอบการและแผนที่)
3. เชื่อมโฟลเดอร์ไฟล์แนบ (`storage:link`)
4. ถามชื่อ/อีเมล/รหัสผ่าน **ผู้ดูแลระบบคนแรก**
5. ถามชื่อวิทยาลัย

รันซ้ำได้ปลอดภัย — ข้อมูลจังหวัดเขียนทับแบบ upsert และถ้ามีผู้ดูแลระบบอยู่แล้วจะข้ามไป

แบบไม่ถามอะไรเลย (สำหรับสคริปต์ติดตั้ง)

```bash
php artisan app:install \
  --admin-name="ผู้ดูแลระบบ" \
  --admin-email=admin@college.ac.th \
  --admin-password='รหัสผ่านที่ตั้งเอง' \
  --college="วิทยาลัยเทคนิค..." \
  --no-interaction-safe
```

> ⚠️ **อย่ารัน `php artisan db:seed` เปล่า ๆ** — `DatabaseSeeder` เป็นข้อมูลตัวอย่างสำหรับนักพัฒนา
> มีบัญชีทดสอบและนักศึกษาปลอม `app:install` เรียกเฉพาะ seeder ข้อมูลจังหวัดที่จำเป็นเท่านั้น

---

## 5. ตั้งค่าเว็บเซิร์ฟเวอร์

**Document root ต้องชี้ที่ `retc-cts/public`** ไม่ใช่ราก repo

สิทธิ์โฟลเดอร์

```bash
chmod -R 775 storage bootstrap/cache
```

ปิดท้ายด้วย

```bash
php artisan optimize
```

---

## 6. ตั้ง cron

| ความถี่ | คำสั่ง | ทำอะไร |
| --- | --- | --- |
| ทุก 1 นาที | `php /path/retc-cts/artisan schedule:run` | สำรองฐานข้อมูลอัตโนมัติ ล้าง session หมดอายุ |
| ทุก 5 นาที | `php /path/retc-cts/artisan queue:work --stop-when-empty --tries=3` | ระบายคิวนำเข้า CSV และแจ้งเตือน |

บน VPS ที่มี root ใช้ [deploy/supervisor-retc-cts-worker.conf](../deploy/supervisor-retc-cts-worker.conf) แทน cron ตัวที่สองได้

---

## 6.5 ติดตั้งผ่านหน้า Plesk (ไม่ต้องใช้ SSH)

โฮสต์ไทยหลายเจ้า เช่น HostAtom ใช้ Plesk ซึ่งมีเครื่องมือครบพอที่จะติดตั้งจากหน้าเว็บได้ทั้งหมด
ตรวจก่อนว่าใน **Websites & Domains → Dev Tools** มี **Git** และ **PHP Composer** และที่
**PHP** เป็นเวอร์ชัน 8.2 ขึ้นไป

| ลำดับ | เมนูใน Plesk | ทำอะไร |
| --- | --- | --- |
| 1 | Databases → Add Database | สร้าง database + user จดชื่อและรหัสผ่านไว้ (Plesk มักเติม prefix ให้) |
| 2 | Dev Tools → **Git** → Add Repository | Remote URL `https://github.com/rattanasakking/retc-cts.git` · branch `main` · deployment path `httpdocs` |
| 3 | Dev Tools → **PHP Composer** | กด Install ที่โปรเจกต์ (เลือก `--no-dev`) — Plesk จะสร้าง `vendor/` ให้เอง |
| 4 | Files | คัดลอก `.env.production.example` เป็น `.env` แล้วแก้ค่า DB, `APP_URL`, `MAIL_*` |
| 5 | Scheduled Tasks → Run a PHP script | script `httpdocs/artisan`, arguments `key:generate --force` → **Run Now** |
| 6 | Scheduled Tasks (ตัวเดิม) | เปลี่ยน arguments เป็น `app:install --admin-email=... --admin-password=... --college="..." --no-interaction-safe` → **Run Now** แล้วลบ task ทิ้ง |
| 7 | Hosting & DNS → Hosting Settings | **Document root** เปลี่ยนเป็น `httpdocs/public` — ข้อนี้ห้ามลืม |
| 8 | Security → SSL/TLS Certificates | ออกใบรับรอง Let's Encrypt แล้วเปิด redirect เป็น https |
| 9 | Files | ตั้งสิทธิ์โฟลเดอร์ `storage` และ `bootstrap/cache` เป็น 775 |
| 10 | Scheduled Tasks | เพิ่ม cron ตามข้อ 6 ด้านบน |

ตั้ง **Additional deployment actions** ในหน้า Git ไว้ด้วย จะได้อัปเดตรอบหน้าจบในคลิกเดียว

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

---

## 7. กรณีโฮสต์ที่ไม่มี SSH

ยังติดตั้งได้ แต่ต้องอ้อม

1. รัน `composer install --no-dev` บนเครื่องอื่น แล้วอัปโหลดทั้งโปรเจกต์รวม `vendor/`
2. `.env` — สร้างและแก้ผ่าน File Manager, ใส่ `APP_KEY` ที่ได้จาก `php artisan key:generate --show` บนเครื่องอื่น
3. แทนการรัน artisan ให้ใช้ **Scheduled Tasks → Run a PHP script** ชี้ที่ `retc-cts/artisan` แล้วใส่ arguments:
   - `migrate --force`
   - `db:seed --class=Database\Seeders\ThaiGeographySeeder --force`
   - `optimize`
4. สร้างบัญชีผู้ดูแลระบบด้วย Scheduled Task เรียก `app:install` พร้อมออปชันข้างบน
5. สิทธิ์โฟลเดอร์ `storage` และ `bootstrap/cache` ตั้ง 775 ผ่าน File Manager

**ฟีเจอร์ที่ใช้ไม่ได้บนโฮสต์แบบนี้** — สำรอง/กู้คืนข้อมูลในหน้าตั้งค่า เพราะเรียก `mysqldump`/`mysql`
ผ่าน `proc_open` ซึ่งมักถูกปิด ให้ใช้ Export/Import ของ phpMyAdmin แทน

---

## 8. ตั้งค่าให้เป็นของวิทยาลัยตัวเอง

เข้าเว็บ ล็อกอินด้วยบัญชีที่สร้างไว้ แล้วไปที่ **ตั้งค่าระบบ**

| หน้า | ตั้งอะไร |
| --- | --- |
| ข้อมูลระบบ | ชื่อระบบ ชื่อย่อ ชื่อวิทยาลัย โลโก้ สีหลัก อีเมล/เบอร์ติดต่อ — มีผลทั้งเว็บ รายงาน PDF และไอคอนบนมือถือ |
| ปีการศึกษา | สร้างปีการศึกษาและกำหนดปีปัจจุบัน |
| จัดการผู้ใช้งาน | เพิ่มครู หัวหน้าแผนก ผู้บริหาร |
| ฐานข้อมูลบริษัท | นำเข้ารายชื่อนิติบุคคลจาก DBD Open Data เพื่อช่วยเติมชื่อสถานประกอบการ |

จากนั้นนำเข้ารายชื่อนักศึกษาที่ **นำเข้า CSV** (รองรับทั้งไฟล์เทมเพลตของระบบ และรายงานติดตามภาวะการมีงานทำจากระบบ SIS โดยตรง)

---

## 9. อัปเดตระบบเมื่อมีเวอร์ชันใหม่

```bash
cd /path/to/retc-cts
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```

ไฟล์ frontend ที่ build แล้วมากับ git จึงไม่ต้องรัน npm บนเซิร์ฟเวอร์

---

## 10. เรื่องที่ต้องรับผิดชอบเอง

- **PDPA** — ระบบเก็บเลขบัตรประชาชนและวันเกิด แต่ละสถานศึกษาต้องมีฐานทางกฎหมายและประกาศความเป็นส่วนตัวของตนเอง
  โดยเฉพาะหน้าสาธารณะที่ยืนยันตัวตนด้วยชื่อ + วันเดือนปีเกิด
- **การสำรองข้อมูล** — ระบบสำรองอัตโนมัติทุกวันตี 2 เก็บ 30 ชุด แต่ควรมีสำเนานอกเซิร์ฟเวอร์ด้วย
- **สิทธิ์การใช้โค้ด** — repo นี้ยังไม่ได้กำหนดสัญญาอนุญาต ติดต่อผู้ดูแลก่อนนำไปใช้หรือดัดแปลง
