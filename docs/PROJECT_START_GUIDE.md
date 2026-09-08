# PROJECT_START_GUIDE.md

## 1. Create project

```bash
composer global require laravel/installer
laravel new construction-company
cd construction-company
```

## 2. Laravel bootstrap

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## 3. SQLite local setup

### Windows
```powershell
New-Item database\database.sqlite -ItemType File
```

### `.env`
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
QUEUE_CONNECTION=database
```

## 4. Add `Src\\` autoload if using `src/`

In `composer.json`:
```json
"autoload": {
  "psr-4": {
    "App\\": "app/",
    "Database\\Factories\\": "database/factories/",
    "Database\\Seeders\\": "database/seeders/",
    "Src\\": "src/"
  }
}
```

Then:
```bash
composer dump-autoload -o
```

## 5. Frontend setup

```bash
npm install
npm run dev
```

If `vite` is not recognized:
```bash
npm install
npm run build
```

## 6. Queue tables and migrate

```bash
php artisan queue:table
php artisan migrate
```

If you see `no such table: content_clusters`, confirm SEO migrations exist and rerun:
```bash
php artisan migrate
```

## 7. Run app

### Web app
```bash
php artisan serve
```

### SEO queue worker
```bash
php artisan queue:work --queue=seo
```

## 8. Useful SEO commands

```bash
php artisan seo:sync-clusters
php artisan seo:generate-page 1
php artisan seo:qa 1
php artisan seo:publish 1
```

## 9. Recommended debug commands

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list
php artisan test
```

## 10. Laravel Boost

Install:
```bash
composer require laravel/boost --dev
php artisan boost:install
```

Use Boost to enhance agent tooling, but keep this skill pack as the project-specific rule layer.
