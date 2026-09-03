# 11 — Deployment Specification

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [04_SYSTEM_ARCHITECTURE](./04_SYSTEM_ARCHITECTURE.md), [10_SECURITY_SPECIFICATION](./10_SECURITY_SPECIFICATION.md)

---

## 1. Deployment Strategy

### 1.1 Environment Overview

| Environment | Purpose | Infrastructure | Data |
|-------------|---------|---------------|------|
| **Local** | Development | `php artisan serve` + `npm run dev` | Seeded test data |
| **Staging** | Pre-production testing | Same VPS or separate cheap VPS | Sanitized copy of production |
| **Production** | Live application | VPS ($5-12/month) | Real financial data |

### 1.2 Deployment Approach

**Simple deployment** suitable for a single-developer project:
- Git-based deployment (push to main → deploy)
- No CI/CD pipeline for V1 (manual deploy script)
- Zero-downtime not critical for single-user app
- V1.5+: Consider GitHub Actions for automated deployment

---

## 2. Infrastructure Architecture

### 2.1 Single VPS Architecture

```
┌─────────────────────────────────────────┐
│          VPS ($5-12/month)               │
│  Ubuntu 24.04 LTS                        │
│                                          │
│  ┌──────────────────────────────────────┐│
│  │           Nginx (reverse proxy)      ││
│  │  - HTTPS termination (Let's Encrypt) ││
│  │  - Static file serving               ││
│  │  - Gzip compression                  ││
│  └────────────┬─────────────────────────┘│
│               │                          │
│  ┌────────────┴─────────────────────────┐│
│  │       PHP-FPM 8.3                     ││
│  │  - Laravel application               ││
│  │  - Inertia.js SSR (optional)         ││
│  └──────────────────────────────────────┘│
│                                          │
│  ┌──────────────────────────────────────┐│
│  │       PostgreSQL 16                   ││
│  │  - Primary database                  ││
│  └──────────────────────────────────────┘│
│                                          │
│  ┌──────────────────────────────────────┐│
│  │       Redis 7                         ││
│  │  - Session store                     ││
│  │  - Cache                             ││
│  │  - Queue broker                      ││
│  └──────────────────────────────────────┘│
│                                          │
│  ┌──────────────────────────────────────┐│
│  │       Supervisor                      ││
│  │  - Laravel queue worker (1 process)  ││
│  └──────────────────────────────────────┘│
│                                          │
│  ┌──────────────────────────────────────┐│
│  │       Cron                            ││
│  │  - Laravel scheduler (every minute)  ││
│  └──────────────────────────────────────┘│
└─────────────────────────────────────────┘
```

### 2.2 VPS Provider Comparison

| Provider | Plan | RAM | CPU | Storage | Price/month | Recommendation |
|----------|------|-----|-----|---------|-------------|----------------|
| **Hetzner** | CX22 | 4GB | 2 vCPU | 40GB SSD | €4.35 (~$5) | **Best value** |
| **DigitalOcean** | Basic | 2GB | 1 vCPU | 50GB SSD | $6 | Good, familiar UI |
| **Vultr** | Cloud Compute | 2GB | 1 vCPU | 55GB SSD | $6 | Good performance |
| **Contabo** | VPS S | 8GB | 4 vCPU | 50GB SSD | €5.99 (~$7) | Most resources |
| **IDCloudHost** | VPS | 2GB | 2 vCPU | 20GB SSD | Rp 80,000 (~$5) | Indonesian provider |

**Recommended**: Hetzner CX22 (€4.35/month) — best price/performance, reliable, European data center. OR IDCloudHost for lower latency from Indonesia.

### 2.3 Minimum VPS Requirements

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| RAM | 2GB | 4GB |
| CPU | 1 vCPU | 2 vCPU |
| Storage | 20GB SSD | 40GB SSD |
| OS | Ubuntu 22.04+ LTS | Ubuntu 24.04 LTS |
| PHP | 8.3+ | 8.3 |
| PostgreSQL | 15+ | 16 |
| Redis | 6+ | 7 |

---

## 3. Domain & SSL

### 3.1 Domain Options

| Option | Cost | Notes |
|--------|------|-------|
| Custom domain (`.com`) | ~$10-15/year | Professional, recommended for production |
| Custom domain (`.id` / `.co.id`) | ~Rp 100,000-200,000/year | Local feel |
| Subdomain of free service | Free | Not recommended for production financial app |
| IP address only | Free | Development only |

**Recommendation**: Purchase a `.com` or `.id` domain (~$10-15/year).

### 3.2 SSL Certificate

- **Let's Encrypt**: Free, auto-renewable, sufficient for all uses
- **Setup**: Certbot with auto-renewal via cron
- **Renewal**: Every 90 days (auto via certbot timer)

---

## 4. Server Setup

### 4.1 Initial Server Provisioning

```bash
# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install essentials
sudo apt install -y git curl unzip nginx supervisor certbot python3-certbot-nginx

# 3. Install PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis

# 4. Install PostgreSQL 16
sudo apt install -y postgresql-16 postgresql-client-16

# 5. Install Redis
sudo apt install -y redis-server

# 6. Install Node.js 22 (for building assets)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# 7. Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 4.2 Nginx Configuration

```nginx
server {
    listen 80;
    server_name finance.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name finance.example.com;
    root /var/www/finance/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/finance.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/finance.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;
    gzip_min_length 1000;
    
    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2|woff|ttf|webp|webm)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # PWA manifest and service worker
    location = /manifest.webmanifest {
        types { application/manifest+json webmanifest; }
        expires 1d;
    }
    location = /sw.js {
        expires off;
        add_header Cache-Control "no-store, no-cache, must-revalidate";
    }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4.3 Supervisor Configuration (Queue Worker)

```ini
# /etc/supervisor/conf.d/finance-worker.conf
[program:finance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/finance/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/finance/storage/logs/worker.log
stopwaitsecs=3600
```

### 4.4 Cron (Scheduler)

```cron
# /etc/cron.d/finance-scheduler
* * * * * www-data cd /var/www/finance && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Deployment Script

```bash
#!/bin/bash
# deploy.sh — Run on the VPS

set -e

APP_DIR="/var/www/finance"
BRANCH="main"

echo "🚀 Deploying Finance App..."

cd $APP_DIR

# 1. Pull latest code
echo "📥 Pulling latest code..."
git pull origin $BRANCH

# 2. Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Install Node dependencies and build assets
echo "🏗️  Building frontend assets..."
npm ci
npm run build

# 4. Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# 5. Clear and rebuild caches
echo "🔄 Clearing caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Restart queue worker
echo "♻️  Restarting queue worker..."
php artisan queue:restart

# 7. Restart PHP-FPM
echo "🔁 Restarting PHP-FPM..."
sudo systemctl reload php8.3-fpm

echo "✅ Deployment complete!"
```

### 5.1 Deployment Checklist

- [ ] `.env` file configured with production values
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Database password is strong
- [ ] Redis password is set
- [ ] HTTPS certificate is valid
- [ ] Webhook URLs are configured with provider
- [ ] AI API keys are valid
- [ ] Storage directory is writable
- [ ] Log directory is writable
- [ ] Queue worker is running
- [ ] Scheduler is running
- [ ] Backup is configured

---

## 6. Backup Strategy

### 6.1 Database Backup

```bash
#!/bin/bash
# backup-db.sh — Run daily via cron

BACKUP_DIR="/var/backups/finance"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="finance"
DB_USER="finance"
RETENTION_DAYS=30

mkdir -p $BACKUP_DIR

# Dump database
pg_dump -U $DB_USER $DB_NAME | gzip > "$BACKUP_DIR/db_${TIMESTAMP}.sql.gz"

# Remove backups older than retention period
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: db_${TIMESTAMP}.sql.gz"
```

### 6.2 Backup Schedule

| What | Frequency | Retention | Method |
|------|-----------|-----------|--------|
| Database | Daily at 2:00 AM WIB | 30 days | pg_dump + gzip |
| Application files | Weekly | 4 weeks | tar + gzip |
| .env file | After changes | Indefinite | Encrypted copy |

### 6.3 Offsite Backup (Recommended)

```bash
# Upload to remote storage (S3, Backblaze B2, or another VPS)
# Using rclone for provider-agnostic remote storage
rclone copy "$BACKUP_DIR/db_${TIMESTAMP}.sql.gz" remote:finance-backups/
```

### 6.4 Backup Verification

Monthly: Restore a backup to a test database and verify data integrity.

```bash
# Restore test
gunzip -k /var/backups/finance/db_latest.sql.gz
psql -U finance finance_test < /var/backups/finance/db_latest.sql
php artisan app:verify-balances --database=test
```

---

## 7. Monitoring

### 7.1 Application Monitoring

| Tool | Purpose | Cost |
|------|---------|------|
| Laravel Log | Error/info logging | Free (built-in) |
| Uptime Kuma | Uptime monitoring (self-hosted) | Free (on same or separate VPS) |
| Laravel Telescope | Debug dashboard (dev/staging only) | Free |
| Sentry | Error tracking (V1.5) | Free tier (5K events/mo) |

### 7.2 Server Monitoring

```bash
# Basic monitoring via cron (email/webhook alert on issues)
# Check disk space
df -h / | awk 'NR==2 {if ($5+0 > 85) print "ALERT: Disk usage " $5}'

# Check memory
free -m | awk 'NR==2 {if ($3/$2*100 > 90) print "ALERT: Memory usage high"}'

# Check if services are running
systemctl is-active --quiet php8.3-fpm || echo "ALERT: PHP-FPM down"
systemctl is-active --quiet postgresql || echo "ALERT: PostgreSQL down"
systemctl is-active --quiet redis-server || echo "ALERT: Redis down"
systemctl is-active --quiet nginx || echo "ALERT: Nginx down"
```

### 7.3 Application Health Endpoint

```php
// GET /health (public, no auth)
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        Redis::ping();
        return response()->json(['status' => 'ok'], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error'], 500);
    }
});
```

---

## 8. Performance Optimization

### 8.1 PHP Configuration

```ini
; /etc/php/8.3/fpm/conf.d/99-finance.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  ; Production only (restart FPM to pick up changes)

memory_limit=256M
max_execution_time=30
upload_max_filesize=10M
post_max_size=10M
```

### 8.2 PostgreSQL Tuning (for 2-4GB VPS)

```ini
# /etc/postgresql/16/main/postgresql.conf
shared_buffers = 512MB           # 25% of RAM
effective_cache_size = 1536MB    # 75% of RAM
work_mem = 16MB
maintenance_work_mem = 128MB
wal_buffers = 16MB
random_page_cost = 1.1           # SSD
effective_io_concurrency = 200   # SSD
```

### 8.3 Redis Configuration

```conf
# /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

---

## 9. Development Environment

### 9.1 Local Development Setup

```bash
# Prerequisites: PHP 8.3, Composer, Node.js 22, PostgreSQL, Redis

# Clone repository
git clone <repo-url> finance
cd finance

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Create database
createdb finance

# Run migrations and seed
php artisan migrate
php artisan db:seed

# Start development servers (2 terminals)
php artisan serve          # Terminal 1: Laravel on :8000
npm run dev                # Terminal 2: Vite on :5173
```

### 9.2 Alternative: Laravel Sail (Docker)

For developers who prefer Docker:

```bash
composer require laravel/sail --dev
php artisan sail:install --with=pgsql,redis
./vendor/bin/sail up
```

---

## 10. Environment Comparison

| Aspect | Development | Production |
|--------|-------------|------------|
| `APP_DEBUG` | `true` | `false` |
| `APP_ENV` | `local` | `production` |
| Database | Local PostgreSQL | VPS PostgreSQL |
| Cache | `array` or Redis | Redis |
| Session | `file` | `redis` |
| Queue | `sync` (immediate) | `redis` (async) |
| Assets | Vite dev server (HMR) | Pre-built, cached |
| Error display | Full stack trace | Generic error page |
| Logging | `daily` files | `daily` files + Sentry (V1.5) |
| HTTPS | Optional | Required |
| Scheduler | Manual (`artisan schedule:work`) | Cron |
| Queue worker | Not needed (`sync` driver) | Supervisor |
