# Mess Management App

Laravel `9.52.21` aur React frontend par based responsive mess management application.

## Stack

- Laravel 9.52.21
- React + Laravel Mix
- MySQL
- Sanctum token auth
- Spatie Laravel Permission for RBAC

## Included Modules

- Login
- Dashboard summary
- Members management
- Menu planning
- Expense management
- Payment collection
- Role and permission based authorization

## Demo Login

- Email: `admin@messapp.test`
- Password: `password`

## Database

`.env` aur `.env.example` MySQL database `mess_management` ke liye set hain.

## Useful Commands

```bash
php artisan migrate:fresh --seed
npm run dev
php artisan serve
```

## Production Deploy

Detailed production deployment steps are available in [DEPLOYMENT.md](DEPLOYMENT.md).

Render deployment ke liye Docker setup included hai:

- `Dockerfile`
- `docker/apache-vhost.conf`
- `docker/render-start.sh`

## Notes

- Dashboard calculations manual Google Sheet workflow ko base bana kar seed ki gayi hain.
- Frontend responsive hai aur mobile-first navigation bottom bar ke saath ready hai.
- APIs Sanctum bearer token ke saath exposed hain, is liye same backend ko future React Native app ke saath bhi reuse kiya ja sakta hai.
