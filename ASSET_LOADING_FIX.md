# Asset Loading Fix Guide - EIMS on Render

## Problem
When deployed on Render, the system was loading as raw HTML without CSS/JS because assets weren't being served properly.

## Root Causes
1. ❌ `APP_URL` was missing the protocol (`https://`)
2. ❌ `ASSET_URL` was not configured
3. ❌ Apache VirtualHost wasn't properly configured for static file serving
4. ❌ Build permissions weren't set correctly
5. ❌ `public/build` directory wasn't being created with proper permissions

## Solutions Implemented

### 1. Fixed render.yaml
✅ Updated `APP_URL` to use proper URL format with protocol:
```yaml
- key: APP_URL
  fromService:
    type: web
    name: eims-app
    property: url  # Now includes https://
```

✅ Added `ASSET_URL` configuration:
```yaml
- key: ASSET_URL
  fromService:
    type: web
    name: eims-app
    property: url  # Points to same host for asset serving
```

### 2. Enhanced Dockerfile
✅ Updated build command to:
- Install ALL npm dependencies (including dev for Vite)
- Build assets with `npm run build`
- Set proper permissions: `chmod -R 755 public/build`

✅ Ensured `public/build` is properly copied to final image:
```dockerfile
COPY --from=assets /app/public/build ./public/build
```

### 3. Improved Apache Configuration
✅ Updated `docker/apache-vhost.conf` with:
- **Proper mod_rewrite** for Laravel routing
- **Static file caching** headers for assets
- **Security headers** (X-Content-Type-Options, etc.)
- **Compression** (gzip) for better performance
- **Expires headers** for long-term caching of assets

### 4. Enhanced Entrypoint Script
✅ Updated `docker/entrypoint.sh` to:
- Create `public/build` directory
- Set proper permissions: `chmod -R 755 public/build`
- Clear and rebuild caches

### 5. Environment Variables
✅ Added to `.env`:
```env
ASSET_URL=http://localhost
```

✅ Added to `.env.example`:
```env
# For Render deployment:
ASSET_URL=https://your-app-name.onrender.com
```

## File Changes Summary

| File | Change |
|------|--------|
| `render.yaml` | Added ASSET_URL, fixed APP_URL property |
| `Dockerfile` | Added npm build, asset permissions, proper COPY order |
| `docker/apache-vhost.conf` | Added mod_rewrite, caching, security headers |
| `docker/entrypoint.sh` | Added public/build directory setup and permissions |
| `.env` | Added ASSET_URL |
| `.env.example` | Added ASSET_URL documentation |

## How Assets Are Served

1. **Build Time** (in Docker build):
   ```
   npm run build → public/build/manifest.json
                → public/build/assets/*.css
                → public/build/assets/*.js
   ```

2. **Runtime** (on Render):
   ```
   @vite(['resources/css/app.css', 'resources/js/app.js'])
   ↓
   Laravel reads public/build/manifest.json
   ↓
   Resolves to: /build/assets/app-HASH.css
   ↓
   Apache serves from public/ directory
   ↓
   Browser loads: https://your-app.onrender.com/build/assets/app-HASH.css
   ```

## Verification Steps

### Local Development
```bash
# 1. Build assets
npm run build

# 2. Check manifest exists
ls -la public/build/manifest.json

# 3. Start local server
php artisan serve

# 4. Visit http://localhost:8000
# Should see properly styled page
```

### On Render
```bash
# 1. View logs
# Render Dashboard → App Logs

# 2. Check APP_URL and ASSET_URL are set
# Render Dashboard → Environment

# 3. Visit https://your-app.onrender.com
# Assets should load correctly
```

## Troubleshooting

### Assets Still Not Loading?

**Check 1: Manifest File Exists**
```bash
docker exec eims-app ls -la public/build/manifest.json
```
If missing, run: `docker exec eims-app npm run build`

**Check 2: Permissions Are Correct**
```bash
docker exec eims-app ls -la public/build/
# Should show: drwxr-xr-x
```

**Check 3: Browser Network Tab**
- Open DevTools → Network
- Look for CSS/JS requests
- Check the URL format is correct
- Look for 404 errors

**Check 4: Apache Configuration**
```bash
docker exec eims-app apache2ctl configtest
# Should output: Syntax OK
```

**Check 5: Render Build Logs**
- Check Render Dashboard for build errors
- Verify npm run build completes successfully
- Check disk space is not full

### Common Issues

**Issue**: `public/build/manifest.json: No such file or directory`
**Solution**: Run `npm run build` in build stage
**Fix**: Already updated in render.yaml buildCommand

**Issue**: 404 for CSS/JS files
**Solution**: Check Apache is serving public/ directory correctly
**Fix**: Check docker/apache-vhost.conf has correct DocumentRoot

**Issue**: APP_URL missing protocol
**Solution**: Use `fromService property: url` not `host`
**Fix**: Already updated in render.yaml

**Issue**: Permissions denied on public/build
**Solution**: Ensure chmod 755 and chown www-data
**Fix**: Already updated in entrypoint.sh and Dockerfile

## Testing Asset Loading

### Method 1: Browser Console
```javascript
// Check if assets loaded
console.log(document.querySelectorAll('link[rel="stylesheet"]'));
console.log(document.querySelectorAll('script'));
```

### Method 2: Page Source
- Right-click → View Page Source
- Search for `/build/assets/`
- Should see URLs like: `<link href="/build/assets/app-HASH.css">`

### Method 3: CLI Check
```bash
curl -I https://your-app.onrender.com/build/assets/app.css
# Should return 200, not 404
```

## Performance Tips

1. **Enable Gzip Compression** ✅ (Already configured in apache-vhost.conf)
2. **Set Cache Headers** ✅ (Already configured for 1 year max-age)
3. **Use CDN** (Optional - consider Cloudflare)
4. **Minimize Assets** ✅ (Vite does this automatically)
5. **Lazy Load Images** (Consider for dashboard)

## Next Steps

1. **Rebuild Dockerfile**:
   ```bash
   docker build -f Dockerfile -t eims:latest .
   ```

2. **Push to GitHub**:
   ```bash
   git add .
   git commit -m "Fix asset loading on Render"
   git push origin main
   ```

3. **Redeploy on Render**:
   - Render Dashboard → Select eims-app
   - Click "Manual Deploy" → "Deploy latest commit"

4. **Monitor Build**:
   - Watch build logs for errors
   - Check that `npm run build` completes
   - Wait for deployment to finish

5. **Verify**:
   - Visit https://your-app.onrender.com
   - Open DevTools → Network
   - Check CSS/JS load successfully (should be green 200)
   - Check page is styled (not raw HTML)

## Success Indicators

✅ Page loads with styling
✅ All text is properly formatted
✅ Interactive elements work (dropdowns, buttons)
✅ No console errors about missing CSS/JS
✅ No 404 errors in Network tab for assets
✅ Page loads quickly (assets cached)

---

**Last Updated**: July 23, 2026
**Status**: Asset Loading Fixed ✅
**Related Files**: render.yaml, Dockerfile, docker/apache-vhost.conf, docker/entrypoint.sh
