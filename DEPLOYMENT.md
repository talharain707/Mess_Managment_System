# Deployment Guide

Yeh app Laravel 9 + React (Laravel Mix) + MySQL par based hai.

## Render Deployment

Render par PHP/Laravel deploy karne ka recommended tareeqa Docker hai. Is repo mein `Dockerfile` aur Render start script add hain.

### 1. Database

Render ka managed database PostgreSQL hai. Testing/demo deployment ke liye:

1. Render Dashboard mein **New > PostgreSQL** create karein.
2. Same region select karein jis region mein web service banani hai.
3. Database ka **Internal Database URL** copy karein.

> Agar aap MySQL hi use karna chahte hain, to external MySQL provider ka `DATABASE_URL` use karein aur `DB_CONNECTION=mysql` rakhein.

### 2. Web Service

Fastest option: Render Dashboard mein **New > Blueprint** select karein aur GitHub repo connect karein. Repo root mein `render.yaml` web service aur PostgreSQL database dono define karta hai.

Manual option:

1. Code GitHub/GitLab/Bitbucket repo par push karein.
2. Render Dashboard mein **New > Web Service** create karein.
3. Repo connect karein.
4. Runtime/Environment mein **Docker** select karein.
5. Instance type free rakh sakte hain testing ke liye.

Dockerfile automatically:

- Composer production dependencies install karega
- npm assets build karega
- Apache ko Laravel `public` folder se serve karega
- Render ke `PORT` environment variable par bind karega
- App start par `php artisan migrate --force` run karega

### 3. Render Environment Variables

Render service ke **Environment** section mein ye variables set karein:

```env
APP_NAME=Mess Management
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key

DB_CONNECTION=pgsql
DATABASE_URL=your-render-internal-postgres-url

LOG_CHANNEL=stderr
LOG_LEVEL=error
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

`APP_KEY` locally generate karein:

```bash
php artisan key:generate --show
```

Initial demo data/admin user create karne ke liye pehli deploy par temporarily ye env var add karein:

```env
RUN_SEEDER=true
```

Pehli successful deploy aur login ke baad `RUN_SEEDER` remove ya `false` kar dein, warna har restart/deploy par seed data sync hoga aur demo admin password reset ho sakta hai.

Default seeded admin:

- Email: `admin@messapp.test`
- Password: `password`

Login ke baad password foran change karein.

### 4. Render Deploy Notes

- Render free PostgreSQL databases time-limited ho sakti hain; production data ke liye paid DB ya external DB use karein.
- Free web service idle hone par sleep kar sakti hai, first request slow ho sakti hai.
- `php artisan route:cache` is app par abhi run na karein, kyun ke routes mein closures use ho rahe hain.
- Assets Docker build ke during `npm run prod` se generate hotay hain.
- `storage` container filesystem par hai; uploaded files persistent chahiye hon to Render persistent disk ya external storage configure karein.

## Server Requirements

- PHP `8.0` ya us se upar
- MySQL `5.7+` ya `8+`
- Composer
- Node.js `18+` aur npm
- Apache ya Nginx

## 1. Project Upload

Project ko server par upload karein, example:

```bash
/var/www/mess-management
```

## 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run prod
```

## 3. Environment File

`.env.example` ko copy karke `.env` banayein:

```bash
cp .env.example .env
```

Production ke liye in values ko update karein:

```env
APP_NAME="Mess Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

LOG_CHANNEL=stack
LOG_LEVEL=error
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

Phir app key generate karein:

```bash
php artisan key:generate
```

## 4. Database Setup

Server par empty MySQL database bana kar migrations run karein:

```bash
php artisan migrate --seed --force
```

Seed ke baad default admin:

- Email: `admin@messapp.test`
- Password: `password`

Deploy ke baad is password ko foran change karein.

## 5. Optimize Laravel

```bash
php artisan config:cache
php artisan view:cache
```

## 6. Storage Permissions

Laravel ko in folders par write access chahiye:

- `storage`
- `bootstrap/cache`

Linux example:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 7. Web Server Root

Document root hamesha project ke `public` folder par point kare:

```bash
/var/www/mess-management/public
```

## 8. Nginx Example

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/mess-management/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## 9. Apache Notes

- `mod_rewrite` enable hona chahiye
- VirtualHost ka `DocumentRoot` `public` folder ho

Example:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/mess-management/public

    <Directory /var/www/mess-management/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 10. Deploy Commands Summary

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run prod
cp .env.example .env
php artisan key:generate
php artisan migrate --seed --force
php artisan config:cache
php artisan view:cache
```

## 11. Post Deploy Check

- Site root page open ho rahi ho
- Login chal raha ho
- API requests `200` de rahi hon
- `storage/logs/laravel.log` mein errors na hon

## 12. Important Notes

- `node_modules` aur `vendor` ko git mein rakhna zaroori nahi; server par install karna best hai.
- Agar shared hosting use kar rahe hain aur `public` ko direct web root nahi bana sakte, to alag structure ki zarurat hogi.
- Is app ke assets pehle build karne zaroori hain, warna CSS/JS load nahi honge.
