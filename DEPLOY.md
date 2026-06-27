# Deploying banha.shop to shared hosting

This app is configured to run on **standard shared hosting** (cPanel / Hostinger
shared): no long-running processes, no custom ports — just PHP requests + cron +
MySQL. Real-time uses **polling**; background jobs run via a **cron-driven queue
worker**.

---

## 1. Requirements

- PHP **8.3+** (8.4 fine) with the usual Laravel extensions (pdo_mysql, mbstring, openssl, etc.)
- MySQL database
- Cron access (Hostinger shared includes it)
- Composer available (or upload `vendor/` built locally)

---

## 2. Document root

Shared hosting serves `public_html/`. Point the domain's document root at the
project's **`public/`** directory. Two common setups:

- **Set document root to `.../banha/public`** (preferred, if the panel allows it), or
- Put the app one level above `public_html` and symlink:
  `ln -s /home/USER/banha/public /home/USER/public_html`

Never expose the project root — only `public/` should be web-reachable.

---

## 3. First-time setup

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env          # then edit (see below)
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force   # optional: categories + demo data
php artisan storage:link      # so uploaded request/offer images are served
npm ci && npm run build       # build assets locally if no Node on the host,
                              # then upload public/build/
php artisan config:cache route:cache view:cache
```

### `.env` essentials

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://banha.shop
APP_LOCALE=ar

DB_CONNECTION=mysql
DB_DATABASE=...   DB_USERNAME=...   DB_PASSWORD=...

QUEUE_CONNECTION=database     # jobs drained by cron (below)
BROADCAST_CONNECTION=null     # polling; no websockets on shared hosting
FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_STORE=database
```

---

## 4. Cron (the important part)

Shared hosting can't keep `queue:work` running. Instead, add **one** cron entry —
Laravel's scheduler runs every minute and itself drains the queue every minute
(see `routes/console.php`):

```
* * * * * cd /home/USER/banha && php artisan schedule:run >> /dev/null 2>&1
```

That single line covers:
- **Matching jobs & notifications** — `Schedule::command('queue:work --stop-when-empty --max-time=50 ...')->everyMinute()->withoutOverlapping()`
- Any future scheduled tasks (lead expiry, digests, etc.)

> Effect: when a buyer publishes a request, the merchant matching/notifications
> are processed within ~1 minute. If you need it instant, you can instead set
> `QUEUE_CONNECTION=sync` (matching runs inline on publish) — no cron worker
> needed, but publishing waits for matching to finish.

If your panel lets you pick the PHP binary, use the same 8.3+ CLI binary the site
uses (e.g. `/usr/bin/php8.3`).

---

## 5. Real-time (optional, later)

Chat and notifications work today via polling (chat refreshes every ~5s; the
notification list updates on navigation). Websockets are **not** required.

To upgrade to instant delivery without a VPS, use a hosted pusher-compatible
service (Pusher Channels or Ably) — the frontend already speaks that protocol:

1. In `.env`: `BROADCAST_CONNECTION=pusher`, fill `PUSHER_*` keys, and set the
   `VITE_*` vars (uncomment the block in `.env.example`).
2. `npm run build` and redeploy `public/build/`.
3. `php artisan config:cache`.

`resources/js/app.js` auto-loads Echo only when `VITE_REVERB_APP_KEY` (or the
pusher equivalent) is present, so nothing changes until you configure it.

Self-hosted **Reverb** is also supported but needs a long-running process + open
port — only viable on the **VPS**, not shared hosting.

---

## 5b. Replacing an OLD PWA on the same domain (icon cache)

If a previous PWA ran on this domain, devices/browsers cache its service worker,
manifest, and **home-screen icon**. This release handles most of it automatically:

- The service worker `VERSION` is bumped (`public/sw.js`) → on activate it **deletes every cache that doesn't match**, purging the old PWA's caches.
- New, freshly-named **PNG icons** (`icon-192/512/180/maskable`) in the new brand, plus `?v=2` on the manifest/icon links → browsers fetch the new icon, not the cached old one.
- The registration script reloads once when the new SW replaces the old one.

**On every future deploy that changes assets/icon:** bump `VERSION` in `public/sw.js`
(e.g. `v2` → `v3`) so the purge runs again.

**Caveat you can't avoid programmatically:** a user who already *installed* the old
PWA to their home screen — especially on **iOS** — keeps the old launcher icon until
they **remove and re-add** it. New installs and Android (which re-reads the manifest)
pick up the new icon automatically. Tell existing users to delete the old icon and
reinstall once.

## 6. Redeploys

```bash
git pull   # or upload changed files
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build           # if assets changed (or upload prebuilt public/build/)
php artisan config:cache route:cache view:cache
php artisan queue:restart   # tells the next cron worker to pick up new code
```

---

## 7. Quick post-deploy checks

- Visit `/` → loads; `/register` renders RTL Arabic.
- Publish a request as a buyer → within ~1 min a matched merchant sees it under
  **New leads** (confirms cron + queue).
- Upload an image on a request → it displays (confirms `storage:link` + `public` disk).
