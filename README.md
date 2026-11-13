# BizBite POS

Restaurant / QSR billing for Indian outlets — built for Delhi NCR (Delhi, Gurugram, Noida, Ghaziabad, Faridabad).

**Counter (POS):** fast menu grid · veg/non-veg marks · hold & resume bills · cash / UPI / card / credit / split ·
dynamic UPI QR with the exact amount · GST tax invoice (CGST/SGST per slab, SAC 996331) · KOT printing ·
WhatsApp e-bill · reprint today's bills · kitchen order board · shift cash drawer · light/dark theme.

**Owner console:** today's sales · reports + CSV · menu & stock alerts · tables · coupons · staff ·
store & billing settings (GSTIN with check-digit validation, FSSAI, UPI ID) · audit log.

Stack: Laravel 11 · Livewire 3 · Tailwind · SQLite / MySQL / Postgres · Flutter app in `mobile/`.

---

## 1. Run locally

```bash
composer install
npm ci && npm run build          # compiled CSS (without it the app falls back to the Tailwind CDN)
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan bizbite:install      # creates your store + owner login (asks for details)
php artisan serve                # http://127.0.0.1:8000
```

Want sample data to try things out? `php artisan bizbite:install --demo` loads the demo restaurant
(logins `admin@gmail.com` / `cashier@gmail.com`, password `p`). **Never use demo data on a live outlet.**

Tests: `php artisan test`.

## 2. Go-live checklist for a new outlet

1. **Owner console → Store & billing**
   - Trade name, address, PIN, phone
   - Tick *GST registered* and enter the **GSTIN** (bills print as *TAX INVOICE*). Not registered? Leave it
     off — bills print as *Bill of Supply* without tax lines.
   - **FSSAI** 14-digit licence/registration number (mandatory on food bills)
   - **UPI ID** (e.g. `shop@okaxis`) — the POS then shows a QR with the exact bill amount
2. **Menu & stock** — categories, items, price (GST-inclusive), GST slab (5% for most restaurants), veg/non-veg.
3. **Staff** — one cashier login per person (never share the owner login at the counter).
4. **Printer** — any 80mm thermal printer set as the default printer on the counter PC.
   In Chrome print settings: paper 80mm, margins *None*, untick *Headers and footers*.
   For silent one-click printing launch Chrome with `--kiosk-printing`.
5. Do a test bill in each payment mode, cancel it from **Orders**, and check the receipt.

### Counter shortcuts

| Key | Action |
|-----|--------|
| F4 | Search (Enter adds the first match) |
| F6 | Hold the current bill |
| F8 / F9 | Settle as Cash / UPI |
| F2 | Clear bill |
| Esc | Close panels |

## 3. Production deployment (Docker)

```bash
docker build -t bizbite .
docker run -p 10000:10000 \
  -e APP_KEY=base64:... -e APP_URL=https://pos.example.in \
  -e DB_CONNECTION=mysql -e DB_HOST=... -e DB_DATABASE=... -e DB_USERNAME=... -e DB_PASSWORD=... \
  -e OWNER_EMAIL=owner@example.in -e OWNER_PASSWORD='...' -e STORE_NAME='Apna Dhaba' \
  bizbite
```

The entrypoint runs `migrate --force` (never wipes data), creates the owner on first boot from
`OWNER_*` env, starts the scheduler (nightly backup) and a queue worker.

| Env | Purpose |
|-----|---------|
| `APP_KEY` | **Required and permanent.** Generate once with `php artisan key:generate --show`. |
| `APP_ENV=production`, `APP_DEBUG=false` | Always in production. |
| `APP_TIMEZONE=Asia/Kolkata` | Default. Bill times, day-wise bill numbers and reports use IST. |
| `OWNER_EMAIL`, `OWNER_PASSWORD`, `STORE_NAME`, `OWNER_NAME` | First-boot owner account (min 8 chars, letters + numbers). |
| `SEED_DEMO_DATA=true` | Demo instance only. |
| `DB_RESET_ON_BOOT=true` | Demo instance only — **wipes all bills on every restart.** |

Without Docker: point the web server's document root at `public/` (never the project folder),
run `php artisan schedule:work` (or a cron calling `schedule:run` every minute) and
`php artisan config:cache && php artisan view:cache` after each deploy.

## 4. Backups

`php artisan bizbite:backup` writes a gzip snapshot to `storage/app/backups` (SQLite: consistent
`VACUUM INTO` copy, safe during service; MySQL: `mysqldump`; Postgres: `pg_dump`). It runs daily at
03:30 IST and keeps 14 days (`--keep=`).

**Copy that folder off the machine** (Google Drive / S3 / USB) — a backup on the same disk dies with the disk.

Restore (SQLite): stop the app, `gunzip -c bizbite-YYYYMMDD-HHMMSS.sqlite.gz > database/database.sqlite`, start.
Restore (MySQL): `gunzip -c file.sql.gz | mysql -u USER -p DBNAME`.

## 5. Security notes

- `public/adminer` (database console with a password-less login) is **for local development only**.
  It is excluded from Docker images and limited to localhost by `.htaccess`; do not deploy it.
- Keep `.env` out of the web root and out of git.
- Every money-sensitive action (discounts, refunds, cancellations, credit bills, price and settings
  changes) is written to the owner's audit log.

## 6. Theme

Colour tokens live in `resources/views/partials/theme.blade.php` (CSS variables, light + dark);
`tailwind.config.js` maps Tailwind names onto them. Change the `--brand-*` values there to rebrand.
