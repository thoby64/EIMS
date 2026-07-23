# EIMS Simplified Docker Setup - Based on NUTRIFY

## Overview
The EIMS Docker setup has been simplified to match the proven NUTRIFY approach. This significantly reduces complexity and improves reliability on Render.

## Key Changes

### 1. **Dockerfile: Single-Stage Build**

**Before**: 3-stage multi-stage build (vendor → assets → final)
**After**: Single-stage build matching NUTRIFY

**Benefits**:
- ✅ Simpler to debug and understand
- ✅ Fewer moving parts = fewer failure points
- ✅ Direct asset copying without intermediate stages
- ✅ All dependencies built in one coherent context

**Process**:
```dockerfile
1. Install PHP 8.4 with Apache
2. Install system dependencies (libpq, imagemagick, etc)
3. Install Composer
4. Copy application code
5. Install PHP dependencies with composer
6. Install Node.js
7. Run npm install
8. Run npm run build (generates public/build/)
9. Configure Apache
10. Set permissions
11. CMD: Clear caches at runtime
```

### 2. **Apache Configuration: Simplified Approach**

**Before**: Custom virtual host file (docker/apache-vhost.conf)
**After**: Direct sed modifications to 000-default.conf + conf-available/laravel.conf

**Changes**:
```bash
# Modify default configuration directly
sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Create comprehensive laravel.conf with all needed directives
a2enconf laravel
a2enmod headers expires deflate
```

**Key Features**:
- ✅ Asset caching headers (31536000 seconds = 1 year)
- ✅ Security headers (X-Frame-Options, X-Content-Type-Options)
- ✅ Gzip compression
- ✅ Proper directory permissions

### 3. **Port Configuration**

**Before**: PORT environment variable (10000) with sed substitution in entrypoint
**After**: Standard port 80 in EXPOSE

**Why**:
- Render Docker containers automatically map port 80 to HTTPS
- No need for special port configuration
- Simpler and more standard

### 4. **Cache Clearing: Runtime vs Build Time**

**Before**: Caches cleared during build
**After**: Caches cleared at container startup via CMD

**Why This Matters**:
```dockerfile
# Old approach (problematic)
- Routes cached during build
- Then copied to container
- Stale routes served to requests
- No way to clear without rebuilding

# New approach (correct)
CMD sh -c 'php artisan route:clear && php artisan cache:clear && apache2-foreground'
- Fresh cache on every container start
- Routes always current
- Cache cleared before Apache starts serving
```

### 5. **Removed Components**

**Removed**: `docker/entrypoint.sh`
- Was handling vendor directory checks (Dockerfile does this now)
- Was configuring port (not needed for Render)
- Was creating directories (Dockerfile does this now)
- Was clearing caches (CMD does this now)

**Removed**: Multi-stage complexity
- No vendor stage
- No node stage
- No asset stage
- Everything in one build

### 6. **render.yaml Simplifications**

**buildCommand - Before**:
```yaml
ls -la vendor || echo "Vendor not found..."
composer install ...
npm ci ...
npm run build ...
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 755 public/build
```

**buildCommand - After**:
```yaml
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan config:cache
```

**Why simpler**:
- ✅ Vendor check not needed (always fails on fresh deploy)
- ✅ Route cache removed (cleared at runtime)
- ✅ View cache removed (cleared at runtime)
- ✅ chmod removed (Dockerfile handles permissions)

**startCommand - Before**:
```yaml
migrations || true
seeder || true
queue:work --daemon
```

**startCommand - After**:
```yaml
migrations || true
seeder || true
(no queue daemon)
```

**Why simpler**:
- ✅ Queue daemon not needed for basic functionality
- ✅ QUEUE_CONNECTION=sync for simpler setup
- ✅ Fewer background processes = more stable

### 7. **Session/Cache Storage**

**Before**: DATABASE drivers
```yaml
SESSION_DRIVER: database
QUEUE_CONNECTION: database
CACHE_STORE: database
```

**After**: FILE drivers
```yaml
SESSION_DRIVER: file
QUEUE_CONNECTION: sync
CACHE_STORE: file
```

**Benefits**:
- ✅ No need for extra database connections
- ✅ Simpler debugging
- ✅ Less resource usage
- ✅ Perfect for single-instance deployment

## How Assets Are Served Now

```
1. Build Time (Render buildCommand):
   - npm install: Install dependencies
   - npm run build: Vite generates public/build/manifest.json and assets
   
2. Docker Build:
   - Assets copied into image at /var/www/html/public/build/
   - Permissions set: chmod -R 755
   
3. Container Runtime:
   - Apache starts with CMD
   - Routes/cache cleared first
   - Serves /var/www/html/public as document root
   - Assets in public/build/ served with:
     - Cache-Control: public, max-age=31536000, immutable
     - Gzip compression enabled
     - .htaccess allows public access
     
4. Browser Request:
   - GET /build/assets/app-HASH.css
   - Apache serves from public/build/assets/
   - Response includes cache headers
   - Browser caches for 1 year ✅
```

## Apache Configuration Details

### Public Directory Serving
```apache
<Directory /var/www/html/public>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Build Assets Caching
```apache
<Directory /var/www/html/public/build>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    ExpiresActive On
    ExpiresDefault "access plus 30 days"
    Header set Cache-Control "public, max-age=31536000, immutable"
</Directory>
```

### Asset File Caching
```apache
<FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
</FilesMatch>
```

### Compression
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml \
        text/css text/javascript application/javascript application/json
    DeflateCompressionLevel 9
</IfModule>
```

## Deployment Steps

### 1. Verify Files
```bash
# Check Dockerfile exists and is simplified
head -50 Dockerfile

# Check render.yaml has simplified commands
grep -A 5 "buildCommand" render.yaml

# Verify vite.config.js
head -20 vite.config.js
```

### 2. Commit Changes
```bash
git add Dockerfile render.yaml NUTRIFY_SIMPLIFIED_SETUP.md
git commit -m "Simplify Docker setup to match NUTRIFY proven approach"
git push
```

### 3. Trigger Render Deployment
- Go to Render Dashboard
- Select `eims-app` service
- Click "Manual Deploy"
- Watch build logs

### 4. Monitor Build Progress
- Stage 1: System dependencies (30-45 seconds)
- Stage 2: Composer install (30-45 seconds)
- Stage 3: Node.js install (15 seconds)
- Stage 4: npm build (30-60 seconds)
- Stage 5: Apache config (5 seconds)
- **Total**: ~2-3 minutes

### 5. Verify Deployment

#### Check Build Logs
```
✅ Should see:
- "apt-get install"
- "composer install"
- "npm install"
- "npm run build"
- No errors or timeouts
```

#### Check Live Application
```bash
# 1. Test page loads
curl -I https://eims-rt8p.onrender.com/login

# 2. Check CSS loads
curl -I https://eims-rt8p.onrender.com/build/assets/app.css
# Should show: Cache-Control: public, max-age=31536000

# 3. Check manifest exists
curl https://eims-rt8p.onrender.com/build/manifest.json | head -20
```

#### Browser Verification
```
1. Open https://eims-rt8p.onrender.com/login
2. Press F12 → Network tab
3. Reload (Ctrl+R)
4. Look for:
   ✅ login document: 200
   ✅ app-HASH.css: 200 (not Mixed Block!)
   ✅ app-HASH.js: 200 (not Mixed Block!)
   ✅ All images: 200
5. Page should display with full styling
6. F12 → Console should show NO errors
```

## Troubleshooting

### Issue: Build fails during npm build
**Solution**: 
```bash
# Check locally first
npm install
npm run build

# Verify public/build/ was created
ls -la public/build/manifest.json
```

### Issue: Assets show 404
**Solution**: 
- Check Apache permissions: `chmod -R 755 public/build`
- Verify DocumentRoot: Should be `/var/www/html/public`
- Check .htaccess in public folder exists

### Issue: CSS still not loading
**Solution**: 
- Clear browser cache: Ctrl+Shift+Delete
- Hard refresh: Ctrl+Shift+R
- Check DevTools Network tab for actual error
- Verify Cache-Control header present

### Issue: Routes not working
**Solution**: 
- The CMD clears routes at startup
- Wait 30 seconds for container to fully start
- Check Apache mod_rewrite is enabled

### Issue: Database connection fails
**Solution**: 
- Verify DB_HOST uses Render service reference
- Check DB credentials match PostgreSQL service
- Ensure SSL mode: DB_SSLMODE=require

## Comparison: NUTRIFY vs EIMS

| Aspect | NUTRIFY | EIMS (After) |
|--------|---------|-------------|
| Build Stages | 1 (single) | 1 (single) ✅ |
| Apache Port | 80 | 80 ✅ |
| Asset Building | Dockerfile | Dockerfile ✅ |
| Cache Clearing | Runtime CMD | Runtime CMD ✅ |
| Session Storage | File | File ✅ |
| Database | PostgreSQL | PostgreSQL ✅ |
| Render Runtime | Docker | Docker ✅ |

## Key Improvements

✅ **Simpler**: Single-stage vs 3-stage build
✅ **Faster**: Fewer build steps, less copying
✅ **Reliable**: Cache cleared at runtime
✅ **Proven**: Follows NUTRIFY's working approach
✅ **Maintainable**: Clear, understandable Dockerfile
✅ **Compatible**: Works with PostgreSQL + Render

## Expected Behavior

After redeployment, EIMS should:
1. ✅ Build successfully in ~2-3 minutes
2. ✅ Start Apache automatically
3. ✅ Clear routes and cache on startup
4. ✅ Serve CSS/JS with 200 status codes
5. ✅ Display login page with full styling
6. ✅ Allow login and access dashboard
7. ✅ Cache static assets for 1 year
8. ✅ Serve with gzip compression

## Files Modified

- ✅ `Dockerfile` - Simplified single-stage
- ✅ `render.yaml` - Simplified build/start commands
- ✅ `NUTRIFY_SIMPLIFIED_SETUP.md` - This documentation
- ❌ `docker/entrypoint.sh` - No longer needed (but kept for reference)
- ❌ `docker/apache-vhost.conf` - No longer used (but kept for reference)

## Next Steps

1. Test locally with: `docker build -t eims:latest . && docker run -p 8080:80 eims:latest`
2. Push to Git
3. Trigger Render redeploy
4. Verify assets load in browser
5. Test login flow
6. Check database queries work
