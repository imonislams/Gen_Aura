# GEN AURA store - Laravel 12 + Inertia.js + React + Tailwind + MySQL

Frontend = `resources/js` (React + Inertia.js); the only Blade views are the Inertia root and PDF templates in `resources/views/exports`.
Backend = `app/Http/Controllers`, `app/Models`, `routes/web.php`.
Database = `database/migrations` + `database/seeders` (or import `database/genaura.sql` in phpMyAdmin).

## Run (Windows: install Laragon first, start Apache + MySQL, open Laragon Terminal)
```
cd path\to\gen-aura
php setup.php
```
It creates the database, installs packages (composer install), writes .env, creates all tables,
adds an admin account (admin@example.com / password by default) + 4 sample products, links storage and starts the server.

Shop : http://localhost:8000
Admin: http://localhost:8000/admin   (admin@example.com / password - override with ADMIN_EMAIL / ADMIN_PASSWORD in .env before seeding)

Next time: `php artisan serve`.

## Manual way
```
composer install
copy .env.example .env      (cp on Mac/Linux)
php artisan key:generate
(create empty MySQL database "gen_aura", set DB_* in .env)
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## Go live
Upload the project, point the domain to the `public` folder, `composer install --no-dev`,
set APP_ENV=production, APP_DEBUG=false and real DB/MAIL values in `.env`,
`php artisan migrate --seed --force`, `php artisan storage:link`. Set ADMIN_EMAIL / ADMIN_PASSWORD in `.env` before seeding to control the admin login.
