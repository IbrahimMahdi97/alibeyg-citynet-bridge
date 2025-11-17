# WordPress Plugin Reload & Cache Clearing Guide

## Problem

After updating the plugin code with the 60-second timeout fix, WordPress is **still using the old code** with a 25-second timeout. This indicates the plugin files haven't been properly reloaded.

---

## Step 1: Verify Current Plugin Status

### Test the Version Endpoint

Visit this URL in your browser:
```
https://alibeyg.com.iq/wp-json/alibeyg/v1/version
```

**What You Should See (if plugin loaded correctly):**
```json
{
  "plugin_version": "0.5.1",
  "api_base": "https://171.22.24.69/api/v1.0/",
  "flight_search_timeout": "60 seconds",
  "retry_enabled": true,
  "max_retries": 3,
  "plugin_loaded": true,
  "php_version": "7.x or 8.x",
  "wordpress_version": "6.x",
  "timestamp": "2025-11-15 XX:XX:XX",
  "status": "Plugin loaded successfully with v0.5.1 updates"
}
```

**If You See:**
- `plugin_version: "0.5.0"` or older → Plugin not reloaded
- `flight_search_timeout: "25 seconds"` → Old code still active
- `api_base: "https://citynet.ir/"` → Old API URL still in use
- Error 404 → Plugin not loaded at all

**If You Get 404 Error:**
The plugin isn't loaded. Skip to Step 2.

**If Timeout Shows 25 seconds:**
WordPress is using cached/old code. Proceed to Step 2.

---

## Step 2: Deactivate and Reactivate Plugin (CRITICAL)

**WordPress REQUIRES this step to reload plugin files!**

### Via WordPress Admin:

1. **Go to:** WordPress Admin → Plugins
2. **Find:** "Alibeyg Citynet Bridge" plugin
3. **Click:** "Deactivate"
4. **Wait:** 3 seconds
5. **Click:** "Activate"

### Via WP-CLI (Recommended if you have SSH access):

```bash
# Deactivate
wp plugin deactivate alibeyg-citynet-bridge

# Wait 2 seconds
sleep 2

# Activate
wp plugin activate alibeyg-citynet-bridge

# Verify
wp plugin list | grep alibeyg
```

### Via Database (Advanced - Only if above methods fail):

```sql
-- Connect to your WordPress database
-- Find the plugin in wp_options table
UPDATE wp_options
SET option_value = REPLACE(option_value, 'alibeyg-citynet-bridge/alibeyg-citynet-bridge.php', '')
WHERE option_name = 'active_plugins';

-- Then reactivate via WordPress admin
```

---

## Step 3: Clear ALL WordPress Caches

### A. Object Cache (Redis/Memcached)

If your site uses Redis or Memcached:

**Via WP-CLI:**
```bash
wp cache flush
```

**Via Plugin:**
- If you have "Redis Object Cache" plugin: Click "Flush Cache" button
- If you have "W3 Total Cache": Performance → Dashboard → "Empty all caches"

**Via Redis CLI:**
```bash
redis-cli FLUSHALL
```

### B. WordPress Transients

**Via WP-CLI:**
```bash
wp transient delete --all
```

**Via Database:**
```sql
DELETE FROM wp_options WHERE option_name LIKE '_transient_%';
DELETE FROM wp_options WHERE option_name LIKE '_site_transient_%';
```

### C. Page Cache Plugins

If you use any of these plugins, clear their cache:

**WP Super Cache:**
```
Settings → WP Super Cache → Delete Cache
```

**W3 Total Cache:**
```
Performance → Dashboard → Empty all caches
```

**WP Rocket:**
```
Settings → WP Rocket → Clear cache
```

**LiteSpeed Cache:**
```
LiteSpeed Cache → Toolbox → Purge → Purge All
```

### D. OPcache (PHP Code Cache) - **MOST IMPORTANT**

This is likely the culprit! PHP caches compiled code.

**Method 1: Via WP-CLI (if available):**
```bash
wp cache flush opcache
```

**Method 2: Restart PHP-FPM:**
```bash
# For PHP 7.4
sudo systemctl restart php7.4-fpm

# For PHP 8.0
sudo systemctl restart php8.0-fpm

# For PHP 8.1
sudo systemctl restart php8.1-fpm

# For PHP 8.2
sudo systemctl restart php8.2-fpm

# If unsure which version:
sudo systemctl restart php*-fpm
```

**Method 3: Create a PHP file to flush OPcache:**

Create this file: `public_html/flush-opcache.php`
```php
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache flushed successfully!";
} else {
    echo "OPcache is not enabled or not available.";
}
```

Visit: `https://alibeyg.com.iq/flush-opcache.php`

**Then DELETE the file immediately for security!**

**Method 4: Via .htaccess (for Apache with FastCGI):**
Add to `.htaccess`:
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=noabort:1]
</IfModule>

# Force PHP to reload
FcgidInitialEnv PHP_FCGI_MAX_REQUESTS 1
```

**Then remove these lines after testing!**

---

## Step 4: Clear Browser Cache

Even after server-side caches are cleared, your browser may still cache the old response.

**Hard Refresh:**
- **Chrome/Edge:** Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- **Firefox:** Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)
- **Safari:** Cmd+Option+R

**Or use Incognito/Private mode:**
- Chrome: Ctrl+Shift+N
- Firefox: Ctrl+Shift+P

---

## Step 5: Verify the Fix

### Test 1: Check Version Endpoint Again

Visit: `https://alibeyg.com.iq/wp-json/alibeyg/v1/version`

**Expected Result:**
```json
{
  "plugin_version": "0.5.1",
  "flight_search_timeout": "60 seconds",
  "api_base": "https://171.22.24.69/api/v1.0/"
}
```

### Test 2: Perform a Flight Search

1. Go to: `https://alibeyg.com.iq/`
2. Search for: NJF → THR, 2 adults
3. Open browser console (F12)
4. Check the error message

**Expected Result:**
- If timeout occurs, it should say **60000+ milliseconds** (not 25002)
- Ideally, the search should complete successfully

---

## Step 6: Server-Level Cache (If Above Steps Fail)

### Check for Server-Side Full Page Cache

Some hosting providers have server-level caching:

**cPanel:**
```
cPanel → Software → Optimize Website → Disable
```

**Nginx FastCGI Cache:**
```bash
# Find cache directory (usually /var/cache/nginx)
sudo rm -rf /var/cache/nginx/*
sudo systemctl reload nginx
```

**Varnish Cache:**
```bash
sudo varnishadm "ban req.url ~ /"
```

**Cloudflare (if using):**
1. Log in to Cloudflare
2. Select your domain
3. Caching → Configuration → Purge Everything

---

## Step 7: Nuclear Option - Force Full Reload

If nothing works, try this:

### 1. Rename Plugin Directory
```bash
cd /path/to/wordpress/wp-content/plugins/
mv alibeyg-citynet-bridge alibeyg-citynet-bridge-temp
```

### 2. Wait 5 Seconds
```bash
sleep 5
```

### 3. Rename Back
```bash
mv alibeyg-citynet-bridge-temp alibeyg-citynet-bridge
```

### 4. Reactivate via WP Admin
Go to Plugins → Activate "Alibeyg Citynet Bridge"

---

## Debugging: Check PHP Error Logs

If the plugin isn't loading at all:

**Find error log location:**
```bash
php -i | grep error_log
```

**Common locations:**
- `/var/log/php-fpm/error.log`
- `/var/log/php7.4-fpm.log`
- `/home/username/logs/error.log` (cPanel)
- `public_html/wp-content/debug.log` (if WP_DEBUG enabled)

**Check last 50 lines:**
```bash
tail -50 /var/log/php-fpm/error.log
```

Look for errors related to `alibeyg-citynet-bridge`.

---

## Quick Checklist

Use this checklist to ensure you've tried everything:

- [ ] Tested `/wp-json/alibeyg/v1/version` endpoint
- [ ] Deactivated and reactivated plugin via WordPress admin
- [ ] Cleared WordPress object cache (Redis/Memcached)
- [ ] Cleared WordPress transients
- [ ] Cleared page cache plugins (WP Rocket, W3TC, etc.)
- [ ] Flushed OPcache (most important!)
- [ ] Restarted PHP-FPM service
- [ ] Cleared browser cache (hard refresh or incognito)
- [ ] Checked version endpoint again
- [ ] Tested flight search
- [ ] Checked timeout value in error (should be 60000ms not 25000ms)
- [ ] Reviewed PHP error logs

---

## Expected Timeline

After completing the steps above:

1. **Version endpoint should show v0.5.1** - Immediately after plugin reactivation
2. **Timeout should increase to 60+ seconds** - Immediately after OPcache flush
3. **Flight search should complete** - Within 60 seconds of searching

---

## Success Criteria

**You'll know it's working when:**

✅ `/wp-json/alibeyg/v1/version` shows `"flight_search_timeout": "60 seconds"`
✅ If timeout occurs, error shows **60000+ milliseconds** (not 25002)
✅ Flight search completes successfully within 60 seconds
✅ Browser console shows `[Alibeyg Citynet] Flight search completed successfully`

---

## If Still Not Working

Contact your hosting provider and ask them to:

1. Flush all PHP OPcache
2. Restart all PHP services
3. Check if there's any server-level page caching
4. Verify file permissions on plugin directory (should be 755)

Or provide me with:
- PHP error logs
- WordPress debug logs
- Response from `/wp-json/alibeyg/v1/version`
- Exact error message from browser console
