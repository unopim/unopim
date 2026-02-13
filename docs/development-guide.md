# UnoPim - Development Guide

## Prerequisites

| Requirement | Version |
|------------|---------|
| PHP | 8.2+ |
| PHP Extensions | curl, fileinfo, gd, intl, mbstring, openssl, pdo, pdo_mysql, tokenizer, zip |
| Composer | 2.5+ |
| Node.js | 18.17.1 LTS+ |
| npm | (bundled with Node) |
| MySQL | 8.0.32+ |
| OR PostgreSQL | 14+ |
| Web Server | Nginx or Apache2 |
| RAM | 8GB minimum |
| Redis | (optional, for queue/cache) |
| Elasticsearch | 8.x (optional, for search) |

---

## Installation

### From Composer (Recommended)
```bash
composer create-project unopim/unopim
cd unopim
php artisan unopim:install
```

### From Git Clone
```bash
git clone https://github.com/unopim/unopim.git
cd unopim
cp .env.example .env
composer install
npm install
php artisan unopim:install
php artisan storage:link
```

### Docker Installation
```bash
git clone https://github.com/unopim/unopim.git
cd unopim
docker-compose up -d
```
- Application: http://localhost:8000
- MySQL: localhost:3306
- Mail (Mailpit): http://localhost:8025

---

## Environment Setup

### Key .env Variables
```env
APP_NAME=UnoPim
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_ADMIN_URL=admin
APP_TIMEZONE=Asia/Kolkata
APP_LOCALE=en_US
APP_CURRENCY=USD

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=unopim
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525

# Optional
ELASTICSEARCH_ENABLED=false
RESPONSE_CACHE_ENABLED=false
ACCESS_TOKEN_TTL=3600
REFRESH_TOKEN_TTL=3600
```

---

## Running the Application

### Development Server
```bash
php artisan serve
# Application available at http://localhost:8000
```

### Frontend Development (Hot Reload)
```bash
npm run dev
# Vite dev server with HMR
```

### Queue Worker (Required for imports/exports)
```bash
php artisan queue:work --queue=system,default
# Or for auto-reload on code changes:
php artisan queue:listen --queue=system,default
```

### Build Frontend for Production
```bash
npm run build
```

---

## Common Development Commands

### Artisan Commands
```bash
# Clear all caches
php artisan optimize:clear

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Run database migrations
php artisan migrate

# Seed database
php artisan db:seed

# Generate application key
php artisan key:generate

# Create storage symlink
php artisan storage:link
```

### Elasticsearch Commands
```bash
# Clear elasticsearch indexes
php artisan unopim:elastic:clear

# Re-index products
php artisan unopim:product:index

# Re-index categories
php artisan unopim:category:index
```

### Queue Management
```bash
# Restart queue workers
php artisan queue:restart

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Testing

### Run All PHP Tests
```bash
php artisan test
# or
./vendor/bin/pest
```

### Run with Parallel Execution
```bash
./vendor/bin/pest --parallel
```

### Run Specific Test Suite
```bash
php artisan test --testsuite="Admin Feature Test"
php artisan test --testsuite="Api Feature Test"
php artisan test --testsuite="Core Unit Test"
```

### Run with Coverage
```bash
./vendor/bin/pest --coverage
```

### Playwright E2E Tests
```bash
cd tests/e2e-pw
npm install
npx playwright install chromium
npx playwright test
npx playwright test --reporter=list
npx playwright show-report  # View HTML report
```

### Code Style
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

---

## Test Suites

| Suite | Location | Type |
|-------|----------|------|
| Core Unit | packages/Webkul/Core/tests/Unit | Unit |
| DataGrid Unit | packages/Webkul/DataGrid/tests/Unit | Unit |
| DataTransfer Unit | packages/Webkul/DataTransfer/tests/Unit | Unit |
| User Feature | packages/Webkul/User/tests/Feature | Feature |
| Admin Feature | packages/Webkul/Admin/tests/Feature | Feature |
| API Feature | packages/Webkul/AdminApi/tests/Feature | Feature |
| Installer | packages/Webkul/Installer/tests/Feature | Feature |
| ElasticSearch | packages/Webkul/ElasticSearch/tests/Feature | Feature |
| Completeness | packages/Webkul/Completeness/tests/Feature | Feature |
| E2E Playwright | tests/e2e-pw/tests/ | E2E (23 specs) |

---

## Package Development

### Package Structure
Each Webkul package follows this pattern:
```
packages/Webkul/{PackageName}/
├── src/
│   ├── Config/           # Package config files
│   ├── Contracts/         # Interfaces
│   ├── Database/
│   │   ├── Factories/     # Model factories
│   │   └── Migrations/    # Schema migrations
│   ├── Http/
│   │   ├── Controllers/   # HTTP controllers
│   │   └── Middleware/     # Middleware
│   ├── Models/            # Eloquent models
│   ├── Providers/
│   │   ├── {Package}ServiceProvider.php
│   │   └── ModuleServiceProvider.php
│   ├── Repositories/      # Data access
│   ├── Resources/
│   │   ├── lang/          # Translations
│   │   └── views/         # Blade templates
│   ├── Routes/            # Route definitions
│   └── Services/          # Business logic
└── tests/
```

### Registering a Package
Packages are registered in `config/concord.php`:
```php
'modules' => [
    \Webkul\Admin\Providers\ModuleServiceProvider::class,
    \Webkul\Product\Providers\ModuleServiceProvider::class,
    // ... more modules
],
```

### Autoloading
Add PSR-4 autoload entry in root `composer.json`:
```json
"autoload": {
    "psr-4": {
        "Webkul\\YourPackage\\": "packages/Webkul/YourPackage/src"
    }
}
```

Then run: `composer dump-autoload`

---

## Key Patterns

### Repository Pattern
All data access goes through repositories:
```php
// Repository class
class ProductRepository extends Repository {
    public function model() {
        return ProductContract::class;
    }
}

// Usage in controller
$this->productRepository->findOrFail($id);
$this->productRepository->create($data);
```

### View Events
Extend templates without modifying core:
```blade
{!! view_render_event('unopim.admin.catalog.products.edit.before') !!}
<!-- Product edit form -->
{!! view_render_event('unopim.admin.catalog.products.edit.after') !!}
```

### ACL (Access Control)
Define permissions in package `Config/acl.php`:
```php
return [
    [
        'key'   => 'catalog.products',
        'name'  => 'admin::app.acl.products',
        'route' => 'admin.catalog.products.index',
        'sort'  => 1,
    ],
];
```

Check in controllers:
```php
bouncer()->allow('catalog.products.create');
```

---

## Contribution Guidelines

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Follow Laravel Pint code style
4. Write tests for new features
5. Run `./vendor/bin/pint` before committing
6. Run `./vendor/bin/pest` to verify tests pass
7. Commit with descriptive messages
8. Push and open a pull request

---

## Upgrade Process

### Manual Upgrade
```bash
# 1. Backup
cp -r . ../unopim-backup
mysqldump -u root -p unopim > unopim-backup.sql

# 2. Download latest release
# 3. Copy .env and storage/
# 4. Install dependencies
composer install
npm install && npm run build

# 5. Run migrations
php artisan migrate

# 6. Clear caches
php artisan optimize:clear
php artisan storage:link

# 7. Restart queue workers
php artisan queue:restart
```

### Automated Upgrade
```bash
./upgrade.sh
```
