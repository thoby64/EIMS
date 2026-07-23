# EIMS Deployment Guide

## Table of Contents
1. [Local Docker Development](#local-docker-development)
2. [Render Deployment](#render-deployment)
3. [Environment Configuration](#environment-configuration)
4. [Post-Deployment Steps](#post-deployment-steps)

## Local Docker Development

### Prerequisites
- Docker and Docker Compose installed
- Git installed
- PostgreSQL 16+ (if not using Docker)

### Quick Start

1. **Clone the repository**
   ```bash
   cd /path/to/EIMS-UPDATED/eims
   ```

2. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies**
   ```bash
   docker-compose exec app composer install
   docker-compose exec app npm install
   ```

4. **Generate application key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

5. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate --force
   ```

6. **Migrate data from MySQL (if applicable)**
   ```bash
   docker-compose exec app php artisan db:seed --class=MigrateDataFromMySQLSeeder
   ```

7. **Build frontend assets**
   ```bash
   docker-compose exec app npm run build
   ```

8. **Access the application**
   - Open `http://localhost` in your browser
   - Default user: admin@eims.local / (check your seeded password)

### Docker Commands

**View logs:**
```bash
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres
```

**Stop containers:**
```bash
docker-compose down
```

**Stop and remove volumes:**
```bash
docker-compose down -v
```

**Execute commands in container:**
```bash
docker-compose exec app php artisan tinker
docker-compose exec app php artisan cache:clear
docker-compose exec postgres psql -U eims -d eimsdata
```

---

## Render Deployment

### Prerequisites
- Render account (https://render.com)
- GitHub repository with the project code
- This `render.yaml` file in the root directory

### Deployment Steps

1. **Push code to GitHub**
   ```bash
   git add .
   git commit -m "Add Docker and Render deployment files"
   git push origin main
   ```

2. **Connect Render to GitHub**
   - Go to [Render Dashboard](https://dashboard.render.com)
   - Click "New +" → "Blueprint"
   - Connect your GitHub repository
   - Select the branch (main/production)

3. **Configure Render**
   - Render will automatically read `render.yaml`
   - Review the service configuration
   - Click "Deploy Blueprint"

4. **Monitor Deployment**
   - Render will build Docker image
   - Install dependencies
   - Run migrations
   - Start services
   - Check logs in Render dashboard

5. **Verify Services**
   - PostgreSQL service should be "Live"
   - App service should be "Live"
   - Check logs for any errors

### Environment Variables on Render

Render automatically sets these from `render.yaml`:
- `APP_KEY` - Generated automatically
- `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` - Set from PostgreSQL service
- `APP_ENV` - Set to `production`
- `APP_DEBUG` - Set to `false`

### Custom Domain Setup

1. In Render dashboard, go to your app's settings
2. Click "Custom Domains"
3. Add your domain (e.g., eims.yourdomain.com)
4. Follow DNS configuration instructions
5. Enable auto-SSL certificate

### Auto-Deployment from GitHub

1. In app settings, ensure "Auto-Deploy" is enabled
2. Any push to connected branch will trigger rebuild
3. Monitor builds in "Events" tab

---

## Environment Configuration

### Local Development (.env)
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=eimsdata
DB_USERNAME=eims
DB_PASSWORD=eims_password_secure
```

### Production (Render)
All variables are configured in `render.yaml` and generated/injected by Render.
Database credentials are automatically passed from PostgreSQL service.

### Sensitive Variables

For production deployments:
- Never commit `.env` files with real credentials
- Use `.env.example` as template
- Render automatically generates `APP_KEY`
- Database passwords are auto-generated

---

## Post-Deployment Steps

### 1. Database Setup
```bash
# SSH into Render app
render-cli run "php artisan migrate --force"

# Seed initial data (if needed)
render-cli run "php artisan db:seed --class=MigrateDataFromMySQLSeeder"

# Create admin user (if applicable)
render-cli run "php artisan tinker"
# In tinker:
# > App\Models\User::create(['name' => 'Admin', 'email' => 'admin@eims.local', 'password' => bcrypt('password')])
```

### 2. Cache Configuration
```bash
render-cli run "php artisan config:cache"
render-cli run "php artisan route:cache"
render-cli run "php artisan view:cache"
```

### 3. Queue Worker (if needed)
The deployment automatically starts queue worker in background.
Monitor with:
```bash
render-cli run "php artisan queue:failed"
render-cli run "php artisan queue:retry all"
```

### 4. Storage Configuration
- Files stored in `storage/app` are ephemeral on Render
- For persistent uploads, configure S3 or Render storage
- Update `.env`: `FILESYSTEM_DISK=s3` and add AWS credentials

### 5. Email Configuration (if applicable)
Set in Render environment variables:
- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.mailtrap.io` (or your provider)
- `MAIL_PORT=2525`
- `MAIL_USERNAME=your_username`
- `MAIL_PASSWORD=your_password`

### 6. Monitoring
- Set up error tracking: [Sentry](https://sentry.io), [Rollbar](https://rollbar.com)
- Monitor logs in Render dashboard
- Set up alerts for critical errors

### 7. SSL Certificate
- Render auto-generates SSL for custom domains
- Verify HTTPS works: https://yourdomain.com
- Check certificate in browser

---

## Troubleshooting

### Common Issues

**Build fails with "npm not found"**
- Check that `package.json` exists
- Verify Node version in Dockerfile (currently Node 20)
- Check `npm run build` script exists in package.json

**Database connection fails**
- Verify `DB_HOST` matches PostgreSQL service name in render.yaml
- Check database credentials match
- Ensure PostgreSQL service is "Live"

**Migrations fail**
- Check migration files for syntax errors
- Verify database user has permissions
- Check PostgreSQL logs for details

**Static assets not loading**
- Verify `npm run build` completes successfully
- Check `public/build` directory exists
- Ensure nginx serves `/public` directory

**Application errors in logs**
```bash
# View app logs on Render
render-cli logs <service-name>

# Local Docker logs
docker-compose logs -f app
```

### Performance Optimization

1. **Enable caching**
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

2. **Optimize autoloader**
   - Already done in build command
   - See: `composer install --optimize-autoloader`

3. **Database indexing**
   - Migrations should include indices
   - Monitor slow queries

4. **CDN for static assets**
   - Configure CloudFlare or similar
   - Set `APP_ASSET_URL` environment variable

---

## Scaling on Render

### Upgrade Plan
1. Go to app settings → Plan
2. Select higher tier (Standard, Pro, etc.)
3. Auto-scaling available on paid plans

### Database Scaling
1. Go to PostgreSQL service settings
2. Upgrade plan or increase disk size
3. No downtime during scaling

### Load Balancing
- Render handles load balancing automatically
- Multiple instances run in parallel on Pro+ plans
- Database connections pooled automatically

---

## Backup & Recovery

### Automated Backups
- PostgreSQL backups: Enabled automatically on Render
- Retention: 7 days (configurable)
- Restore from dashboard if needed

### Manual Backup
```bash
# Backup database
render-cli run "pg_dump -h \$DB_HOST -U \$DB_USERNAME \$DB_DATABASE > backup.sql"

# Backup files (if using local storage)
docker-compose exec app tar -czf storage.tar.gz storage/app
```

---

## Support & Resources

- [Render Documentation](https://render.com/docs)
- [Laravel Documentation](https://laravel.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs)
- [Docker Documentation](https://docs.docker.com)
