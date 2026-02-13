# UnoPim - Deployment Guide

## Infrastructure Requirements

### Minimum Production Requirements
| Component | Requirement |
|-----------|------------|
| Web Server | Nginx or Apache2 |
| PHP | 8.2+ with extensions: curl, fileinfo, gd, intl, mbstring, openssl, pdo, pdo_mysql, tokenizer, zip |
| Database | MySQL 8.0.32+ OR PostgreSQL 14+ |
| RAM | 8GB minimum |
| Node.js | 18.17.1 LTS (build only) |
| Composer | 2.5+ |

### Optional Services
| Service | Purpose |
|---------|---------|
| Redis | Queue processing, caching |
| Elasticsearch 8.x | Full-text product/category search |
| Laravel Horizon | Queue monitoring dashboard |
| Mailpit/SMTP | Email delivery |

---

## Docker Deployment

### Docker Compose Services

```yaml
Services:
  unopim-web:     # Apache + PHP-FPM (Port 8000)
  unopim-mysql:   # MySQL 8 (Port 3306)
  unopim-q:       # Queue Worker (system, default queues)
  unopim-mailpit: # Mail Testing (Port 8025)
```

### Quick Start
```bash
git clone https://github.com/unopim/unopim.git
cd unopim
docker-compose up -d
```

The web entrypoint script (`dockerfiles/web-entrypoint.sh`) handles:
1. One-time initialization (lock file based)
2. `composer install` and `npm install`
3. `php artisan unopim:install -n` (non-interactive)
4. Permission setup (`chown 1001:1001`)
5. Apache startup

### Custom Docker Configuration
- **Web Dockerfile:** `dockerfiles/web.Dockerfile` (base: `webkul/unopim:1.0.1`)
- **Queue Dockerfile:** `dockerfiles/q.Dockerfile`
- **MySQL root password:** `password` (change for production)
- **Persistent volume:** `unopim-mysql-disk`

---

## AWS Cloud Deployment

Pre-configured Amazon Machine Image (AMI) available:
[Launch UnoPim on AWS Marketplace](https://aws.amazon.com/marketplace/pp/prodview-fdyosdv7k3cgw)

---

## CI/CD Pipelines

### GitHub Actions Workflows

#### 1. Linting (`linting_tests.yml`)
- **Trigger:** push, pull_request
- **Tool:** Laravel Pint
- **PHP:** 8.2
- Validates code style on every commit

#### 2. PHP Tests (`pest_tests.yml`)
- **Trigger:** push, pull_request
- **PHP:** 8.2, **Database:** MySQL 8.0 service
- Steps: Install dependencies → Configure env → Install UnoPim → Run Pest (parallel)

#### 3. E2E Tests (`playwright_test.yml`)
- **Trigger:** push, pull_request
- **PHP:** 8.2, **Node:** 18, **Database:** MySQL 8.0
- **Required Secrets:** GROQ_API_KEY, UNOPIM_URL, ADMIN_USERNAME, ADMIN_PASSWORD
- Steps: Install app → Start server → Install Playwright → Run E2E tests → Upload artifacts

---

## Queue Configuration

### Queue Architecture
Two priority queues handle all background processing:

| Queue | Priority | Use Cases |
|-------|----------|-----------|
| `system` | High | Import/export jobs, data indexing, completeness calculation |
| `default` | Normal | Webhooks, notifications, general tasks |

### Production Queue Workers
```bash
# Standard worker
php artisan queue:work --queue=system,default

# Supervisor configuration (recommended)
[program:unopim-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --queue=system,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

---

## Elasticsearch Setup

### Configuration (.env)
```env
ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_CONNECTION=default
ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_USER=
ELASTICSEARCH_PASS=
ELASTICSEARCH_INDEX_PREFIX=unopim
```

### Indexing Commands
```bash
php artisan unopim:elastic:clear      # Clear all indexes
php artisan unopim:product:index      # Index products
php artisan unopim:category:index     # Index categories
```

---

## Performance Optimization

### Cache Configuration
```bash
php artisan config:cache    # Cache configuration
php artisan route:cache     # Cache routes
php artisan view:cache      # Cache compiled views
php artisan optimize        # All optimizations
```

### Response Caching
Enable in `.env`:
```env
RESPONSE_CACHE_ENABLED=true
```

### PHP OPcache
Ensure OPcache is enabled in `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### Laravel Octane
UnoPim supports Laravel Octane for high-concurrency:
```bash
php artisan octane:start --workers=4 --task-workers=6
```

---

## Security Considerations

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Use strong database passwords
- [ ] Configure HTTPS/SSL
- [ ] Set secure session configuration
- [ ] Configure CORS appropriately
- [ ] Use Redis for sessions (not file-based)
- [ ] Rotate API tokens regularly
- [ ] Enable response caching
- [ ] Configure firewall rules for database access
- [ ] Set up log rotation

### Supported Security Versions
| Version | Supported |
|---------|----------|
| 1.0.x | Yes |
| 0.3.x | Yes |
| 0.2.x | Yes |

Report vulnerabilities to: support@webkul.com (72-hour response guarantee)

---

## Monitoring

### Application Logs
```
storage/logs/laravel.log
```

### Queue Monitoring
Laravel Horizon (if configured):
```
http://your-domain.com/horizon
```

### Health Checks
- Web: `GET /` (should redirect to admin login)
- Database: `php artisan migrate:status`
- Queue: `php artisan queue:monitor system,default`
- Elasticsearch: `curl localhost:9200/_cluster/health`

---

## Backup Strategy

### Database
```bash
# MySQL
mysqldump -u root -p unopim > backup_$(date +%Y%m%d).sql

# PostgreSQL
pg_dump -U postgres unopim > backup_$(date +%Y%m%d).sql
```

### Files
```bash
# Application and uploaded files
tar -czf unopim_files_$(date +%Y%m%d).tar.gz \
  .env storage/app/public/ storage/logs/
```

### Automated Backup (crontab)
```bash
0 2 * * * cd /var/www/html && mysqldump -u root -p$PASS unopim | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz
0 3 * * * tar -czf /backups/files_$(date +\%Y\%m\%d).tar.gz /var/www/html/storage/app/public/
```
