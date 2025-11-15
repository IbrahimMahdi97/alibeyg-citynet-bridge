# Deployment Instructions

## Critical: WordPress Plugin Reload Required

After uploading the updated plugin files, you **MUST** reload the plugin for changes to take effect:

### Option 1: Deactivate and Reactivate (Recommended)
1. Go to WordPress Admin → Plugins
2. Find "Alibeyg Citynet Bridge"
3. Click **Deactivate**
4. Wait 2 seconds
5. Click **Activate**

### Option 2: Full Plugin Reinstall
1. Deactivate the plugin
2. Delete the plugin files from `wp-content/plugins/alibeyg-citynet-bridge/`
3. Upload the new plugin files
4. Activate the plugin

### Option 3: Clear All Caches
If using a caching plugin (WP Rocket, W3 Total Cache, etc.):
1. Clear all WordPress caches
2. Clear object cache (if using Redis/Memcached)
3. Clear PHP opcache: `service php-fpm restart` (if you have server access)

## What Changed in v0.5.1

### 1. **API URL Fixed**
- **Before**: `https://citynet.ir/`
- **After**: `https://171.22.24.69/api/v1.0/`

### 2. **Timeout Increased**
- **Before**: 25 seconds
- **After**: 60 seconds (for flight searches)

### 3. **Retry Logic Added**
- Automatically retries failed requests 3 times
- Uses exponential backoff: 2s, 4s, 8s delays

### 4. **New Endpoint**
- New direct endpoint: `/wp-json/alibeyg/v1/flight-search`
- Accepts flight search payload directly (no proxy wrapper)

## Testing the Fix

### Test the Direct Endpoint

```bash
curl -X POST https://alibeyg.com.iq/wp-json/alibeyg/v1/flight-search \
  -H "Content-Type: application/json" \
  -d '{
    "Lang": "FA",
    "TravelPreference": {
      "CabinPref": {"Cabin": "Economy"},
      "EquipPref": {"AirEquipType": "IATA"},
      "FlightTypePref": {
        "BackhaulIndicator": "",
        "DirectAndNonStopOnlyInd": false,
        "ExcludeTrainInd": false,
        "GroundTransportIndicator": false,
        "MaxConnections": 3
      }
    },
    "TravelerInfoSummary": {
      "AirTravelerAvail": {
        "PassengerTypeQuantity": [
          {"Code": "ADT", "Quantity": 1},
          {"Code": "CHD", "Quantity": 0},
          {"Code": "INF", "Quantity": 0}
        ]
      }
    },
    "SpecificFlightInfo": {"Airline": []},
    "OriginDestinationInformations": [
      {
        "OriginLocation": {
          "CodeContext": "IATA",
          "LocationCode": "NJF",
          "MultiAirportCityInd": false
        },
        "DestinationLocation": {
          "CodeContext": "IATA",
          "LocationCode": "DXB",
          "MultiAirportCityInd": true
        },
        "DepartureDateTime": "2025-11-22",
        "ArrivalDateTime": null
      }
    ],
    "DeepLink": 0
  }'
```

### Check WordPress Error Logs

After making a request, check your WordPress error logs for:

```
[Alibeyg Citynet] Flight search request received: {"payload_keys":["Lang","TravelPreference",...]}
[Alibeyg Citynet] Success on attempt 1 for flights/search: HTTP 200
[Alibeyg Citynet] Flight search completed successfully
```

If you see retry attempts:
```
[Alibeyg Citynet] Attempt 1/3 failed for flights/search: cURL error 28...
[Alibeyg Citynet] Attempt 2/3 failed for flights/search: cURL error 28...
[Alibeyg Citynet] Success on attempt 3 for flights/search: HTTP 200
```

## Troubleshooting

### Still Getting 25 Second Timeout?

**Cause**: WordPress hasn't reloaded the plugin files

**Solutions**:
1. Deactivate and reactivate the plugin
2. Clear all caches (WordPress, object cache, opcache)
3. Restart PHP-FPM: `sudo service php-fpm restart`
4. Check file permissions: `chmod 644 includes/class-api-client.php`

### Still Calling Wrong API URL?

**Check**:
```bash
# SSH into your server
grep -r "citynet.ir" wp-content/plugins/alibeyg-citynet-bridge/
```

Should show **NO** results (except in old backup files)

### Getting Connection Errors?

**Verify** the API server is accessible:
```bash
curl -k https://171.22.24.69/api/v1.0/flights/search
```

If this fails, check:
1. Firewall rules (is 171.22.24.69 accessible from your WordPress server?)
2. SSL certificate (the API uses HTTPS)
3. Server IP whitelist (does the API allow your WordPress server's IP?)

## Advanced: Override API URL in wp-config.php

If you need to change the API URL without modifying the plugin:

```php
// Add to wp-config.php (before "That's all, stop editing!")
define('CN_API_BASE', 'https://171.22.24.69/api/v1.0/');
define('CN_API_KEY', 'your-api-key-here'); // Optional
```

## Support

If issues persist after following these steps:
1. Check WordPress debug.log
2. Enable WP_DEBUG in wp-config.php: `define('WP_DEBUG', true);`
3. Check PHP error logs
4. Verify plugin version shows "0.5.1" in WordPress admin
