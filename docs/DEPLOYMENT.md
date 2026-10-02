# Jobsy local setup and deployment

## Local app

MySQL is configured in the backend `.env` with a dedicated account scoped to `jobsy`. Database structure and baseline roles/skills are initialized. No demo users or default admin password are installed.

Start in separate terminals:

```sh
cd /home/elmostafa/Documents/jobsy/project-manager-backend
php artisan serve
php artisan queue:work --tries=3 --timeout=60
```

```sh
cd /home/elmostafa/Documents/jobsy/project-manager-frontend
npm start
```

Open `http://localhost:3000`. Keep the same hostname for frontend/backend cookie authentication. The app's normal backend URL remains `http://localhost:8000`; port 8001 and `/tmp/jobsy-browser.sqlite` are only used for isolated browser checks.

Before creating an admin, configure `ADMIN_EMAIL` and a unique `ADMIN_PASSWORD` of at least 12 characters privately in backend `.env`, then run `php artisan db:seed --class=AdminSeeder`. Existing users are not promoted or their passwords overwritten by this seeder. Remove the admin password from `.env` after creation.

## Production configuration

Use backend `.env.production.example` as a starting point, replacing example domains and supplying actual secrets. Keep `.env` outside Git and web access. Set the frontend `REACT_APP_BACKEND_URL` to the actual HTTPS API origin before building. The SPA and API need a shared parent domain for cookie authentication.

Required host setup:

- PHP 8.2+ with PDO MySQL, mbstring, XML, cURL, fileinfo and the other Composer runtime requirements. Node.js 22.12+ for frontend builds.
- Serve only Laravel's `public/` folder. Serve the frontend `build/` folder with an `index.html` fallback for client routes. Never expose backend project files or `.env`.
- HTTPS on both origins. Set `APP_DEBUG=false`, secure session cookies, actual frontend origin and Sanctum stateful domains. Configure SMTP to send from a verified address.
- Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan db:seed --class=SetupSeeder`, and `php artisan optimize`. New installs require `php artisan key:generate`; preserve an existing app key.
- Run `npm ci --legacy-peer-deps` and `npm run build` in the frontend. Restart queue workers after deployment with `php artisan queue:restart`. Run persistent workers under the host's process manager.
- Configure database/storage backups and confirm a restore in a separate database. Take a backup before migrations. New uniqueness constraints intentionally fail when historical duplicate records exist; review those records rather than deleting them automatically.
- Configure the hosting process to run `php artisan schedule:run` every minute if scheduled jobs are enabled. Production infrastructure changes and external email delivery must be verified on the actual host.

Do not run the demo `DatabaseSeeder` in production. `SetupSeeder` installs only roles and skills. Newsletter signup records subscriptions; outbound campaigns are not configured or sent automatically. Contact messages are available at `/admin/contact-messages` for authenticated admins.

Legal policies and production domain/SMTP/backup settings require the owner's actual configuration; they are not fabricated by the application.

## Validation

Run `php artisan app:production-check` on the configured deployment host. It is read-only and reports missing production settings; the local development environment is expected to fail these checks.

`php artisan test` uses isolated in-memory SQLite, never the application's MySQL database. `npm test` runs frontend regressions. Test real MySQL concurrency/load in a separate database before a production launch.
