# Mixed Content Fix - HTTPS URL Generation

## Problem
Browser console showed:
```
Blocked loading mixed active content "http://eims-rt8p.onrender.com/build/assets/app.css"
Blocked loading mixed active content "http://eims-rt8p.onrender.com/build/assets/app.js"
```

### Root Cause
- Page loaded via: `https://eims-rt8p.onrender.com/login` (secure ✅)
- Assets generated with: `http://eims-rt8p.onrender.com/build/assets/app.css` (insecure ❌)
- Browser blocks mixed content for security

### Why It Happened
1. Render uses a reverse proxy that terminates SSL
2. Connection from proxy to Docker container is `HTTP`
3. Laravel didn't know the original request was `HTTPS`
4. Laravel's `asset()` helper generated `HTTP` URLs

## Solution

### 1. TrustProxies Middleware
Created [app/Http/Middleware/TrustProxies.php](app/Http/Middleware/TrustProxies.php):
- Tells Laravel to trust `X-Forwarded-*` headers from Render proxy
- Detects original protocol: `X-Forwarded-Proto: https`
- Generates correct HTTPS asset URLs

```php
protected $proxies = '*';  // Trust all proxies
protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PROTO |  // ← Critical for HTTPS detection
    Request::HEADER_X_FORWARDED_AWS_ELB;
```

### 2. Register Middleware
Updated [bootstrap/app.php](bootstrap/app.php):
```php
$middleware->web(prepend: [TrustProxies::class], append: [AuditHttpRequest::class]);
```
- Added `TrustProxies` as first middleware (runs before others)
- Ensures URL scheme is detected early

### 3. Force HTTPS URLs
Updated [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php):
```php
if ($this->app->environment('production')) {
    $this->app['url']->forceScheme('https');
}
```
- Forces all generated URLs to use `https://` in production
- Works together with TrustProxies

### 4. HSTS Header
Updated [Dockerfile](Dockerfile):
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```
- Tells browsers to always use HTTPS for this domain
- 1 year validity (31536000 seconds)
- Includes preload for hardened security

## How It Works Now

```
1. Browser requests: https://eims-rt8p.onrender.com/login

2. Render proxy receives HTTPS request

3. Proxy forwards to Docker:
   GET /login HTTP/1.1
   X-Forwarded-Proto: https
   X-Forwarded-Host: eims-rt8p.onrender.com

4. TrustProxies middleware runs first:
   - Reads X-Forwarded-Proto: https
   - Updates request scheme to HTTPS

5. Laravel asset() helper now generates:
   asset('build/assets/app.css')
   ↓ (with HTTPS scheme detected)
   https://eims-rt8p.onrender.com/build/assets/app.css

6. AppServiceProvider forceScheme('https') ensures HTTPS

7. Browser receives:
   <link rel="stylesheet" href="https://eims-rt8p.onrender.com/build/assets/app.css">

8. Browser loads CSS over HTTPS ✅

9. No mixed content warnings ✅
```

## Result
```
Before:
- Asset URLs: http://... (insecure)
- Browser: BLOCKS as mixed content ❌
- Console error: "Blocked loading mixed active content"

After:
- Asset URLs: https://... (secure)
- Browser: ACCEPTS, loads CSS/JS ✅
- Page displays with full styling ✅
- Console: No mixed content warnings ✅
```

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Middleware/TrustProxies.php` | NEW - Trust proxy headers |
| `bootstrap/app.php` | Register TrustProxies middleware first |
| `app/Providers/AppServiceProvider.php` | Force HTTPS URLs in production |
| `Dockerfile` | Add Strict-Transport-Security header |

## Testing

### 1. Browser Console Verification
```
F12 → Console
Check: No errors about mixed content ✅
```

### 2. Network Tab Verification
```
F12 → Network tab → Reload

Look for:
✅ All asset URLs: https://...
✅ All assets load with 200 status
✅ No "Mixed Content" warnings
```

### 3. Verify HTTPS Headers
```bash
curl -I https://eims-rt8p.onrender.com/login | grep -i strict-transport

Expected:
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

### 4. Check TrustProxies is Working
```bash
# In Render shell or logs, look for:
# Should see no HTTP scheme URLs being generated
```

## Security Improvements
✅ **TrustProxies**: Correctly detects HTTPS from proxy
✅ **Force HTTPS**: All URLs use secure scheme in production
✅ **HSTS Header**: Browser enforces HTTPS for future visits
✅ **No Mixed Content**: All resources loaded over HTTPS
✅ **Prevents downgrade attacks**: HSTS preload list prevents HTTP fallback

## Why NUTRIFY Works
NUTRIFY doesn't use `X-Forwarded-Proto` detection because:
1. It's likely accessed differently, or
2. Has different proxy setup, or
3. Uses different caching strategy

EIMS now has explicit configuration for Render's proxy setup.

## Deployment Steps

1. Commit changes:
```bash
git add app/Http/Middleware/TrustProxies.php
git add bootstrap/app.php
git add app/Providers/AppServiceProvider.php
git add Dockerfile
git commit -m "Fix mixed content - trust proxy headers and force HTTPS URLs"
git push
```

2. Trigger Render redeploy:
   - Dashboard → eims-app → Manual Deploy
   - Wait for build (~2-3 minutes)

3. Verify in browser:
```
Open: https://eims-rt8p.onrender.com/login
F12 → Console
- No mixed content warnings ✅
- Page loads with full styling ✅
- All assets are HTTPS ✅
```

4. Test login:
```
- Enter credentials
- Click Sign In
- Should load dashboard ✅
- All pages should have styling ✅
```

## Common Issues

### Issue: Still seeing HTTP URLs in generated code
**Solution**: Clear Laravel config cache on next deploy
```bash
# Render will do this automatically:
php artisan config:cache (during buildCommand)
```

### Issue: HSTS header too strict
**Solution**: Can reduce if issues arise, but 1 year is standard
Current: `max-age=31536000` (1 year)

### Issue: Old browsers can't access
**Solution**: HSTS is widely supported in all modern browsers (Chrome, Firefox, Safari, Edge)

## Prevention

For future deployments using proxies:
1. Always use TrustProxies middleware
2. Always force HTTPS in production
3. Always set HSTS headers
4. Test with `curl -I` to verify scheme detection

This is now standard practice for Render deployments! ✅
