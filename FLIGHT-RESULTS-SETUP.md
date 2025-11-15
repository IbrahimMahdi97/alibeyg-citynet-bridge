# Flight Results Page Setup Guide

## Problem

The flight search widget redirects to `/flight/` page, but that page doesn't automatically load the search results based on the user's selected parameters.

## Solution

Use the **`[alibeyg_flight_results]`** shortcode on your flight results page.

---

## Quick Setup (Recommended)

### Step 1: Edit Your Flight Results Page

1. Go to **WordPress Admin → Pages**
2. Find and edit the page at `/flight/` (or whatever URL you're using for flight results)
3. **Clear all existing content** on the page
4. Add only this shortcode:

```
[alibeyg_flight_results]
```

5. Click **Update**

### Step 2: Test

1. Go to your homepage
2. Search for a flight (e.g., NJF to THR, 2 adults)
3. You should be redirected to `/flight/` and see:
   - Your search parameters displayed at the top
   - "Searching for flights..." loading message
   - Flight results appear after API call completes

---

## How It Works

### Search Flow

```
User fills form → JavaScript stores data → Redirects to /flight/ →
Shortcode loads → JavaScript reads data → Calls API → Shows results
```

### Data Storage

The search widget stores three items in `sessionStorage`:

1. **`flightSearchPayload`** - Complete API payload
2. **`flightSearchParams`** - Human-readable parameters
3. **`autoSearch`** - Flag set to 'true' to trigger auto-search

The results page JavaScript (`flight-results.js`):
- Reads this data from `sessionStorage`
- Makes API call to `/wp-json/alibeyg/v1/flight-search`
- Displays the results

---

## Customizing the Results Display

The shortcode provides two main containers you can customize:

### 1. Search Parameters Container

Displays the user's search criteria:

```html
<div id="flight-search-params">
  <!-- Auto-populated with search details -->
</div>
```

### 2. Results Container

Displays the flight results:

```html
<div id="flight-results">
  <!-- Auto-populated with API response -->
</div>
```

### Custom Results Handler

To customize how results are displayed, add this to your theme's JavaScript:

```javascript
// Listen for results loaded event
window.addEventListener('flightResultsLoaded', function(event) {
  const flightData = event.detail.data;

  console.log('Flight results received:', flightData);

  // Get the results container
  const container = document.getElementById('flight-results');

  // Build your custom HTML here
  let html = '<div class="custom-flights">';

  // Example: Loop through flights and display them
  if (flightData.flights && flightData.flights.length > 0) {
    flightData.flights.forEach(function(flight) {
      html += `
        <div class="flight-card">
          <h3>${flight.airline}</h3>
          <p>From: ${flight.origin} To: ${flight.destination}</p>
          <p>Price: ${flight.price}</p>
        </div>
      `;
    });
  } else {
    html += '<p>No flights found</p>';
  }

  html += '</div>';
  container.innerHTML = html;
});
```

Add this code to:
- **Appearance → Customize → Additional CSS (for inline styles)**
- **Or** your theme's JavaScript file
- **Or** a custom plugin

---

## Advanced Setup (Manual Integration)

If you want to build a completely custom results page without using the shortcode:

### 1. Include the JavaScript

Add to your page template:

```php
<?php
// Enqueue the flight results script
wp_enqueue_script(
  'abg-citynet-flight-results',
  plugin_dir_url(__FILE__) . 'wp-content/plugins/alibeyg-citynet-bridge/assets/js/flight-results.js',
  array(),
  '0.5.1',
  true
);
?>
```

### 2. Add Required HTML Elements

```html
<div id="flight-search-params"></div>
<div id="flight-results"></div>
```

### 3. Handle Results (Optional)

```javascript
window.addEventListener('flightResultsLoaded', function(event) {
  // Your custom code here
});
```

---

## Debugging

### Check Browser Console

Press **F12** → **Console** tab. You should see:

```
[Alibeyg Flight Results] Initializing...
[Alibeyg Flight Results] Auto-search enabled, loading results...
[Alibeyg Flight Results] Search payload: {...}
[Alibeyg Flight Results] Calling API: /wp-json/alibeyg/v1/flight-search
[Alibeyg Flight Results] API Response status: 200
[Alibeyg Flight Results] API Result: {...}
```

### Common Issues

#### "No search parameters found"

**Cause**: User navigated directly to `/flight/` without searching

**Fix**: This is expected behavior. The page only auto-searches when coming from the search widget.

#### Search parameters are wrong (e.g., wrong destination, wrong passenger count)

**Cause**: Page is using cached/old data from `sessionStorage`

**Fix**:
1. Clear browser cache and `sessionStorage`
2. Try searching again
3. Check browser console for the payload being sent

#### "Search failed: cURL error 28"

**Cause**: API timeout (should be fixed with v0.5.1)

**Fix**:
1. Make sure you've updated to plugin v0.5.1
2. Deactivated and reactivated the plugin
3. Check WordPress error logs

---

## Styling the Results Page

The plugin includes basic CSS at `assets/css/flight-results.css`. To customize:

### Option 1: Override in Theme CSS

```css
/* In your theme's style.css */
.alibeyg-flight-results-container {
  max-width: 1400px; /* Wider container */
}

#flight-search-params .search-summary {
  background: #your-color;
  border-left-color: #your-brand-color;
}

.loading-state .spinner {
  border-top-color: #your-brand-color;
}
```

### Option 2: Disable Plugin CSS and Use Your Own

Add to `functions.php`:

```php
// Dequeue default styles
add_action('wp_print_styles', function() {
  wp_dequeue_style('abg-citynet-flight-results');
}, 100);

// Enqueue your custom styles
wp_enqueue_style('my-custom-flight-results', get_stylesheet_directory_uri() . '/css/flight-results.css');
```

---

## Example: Complete Custom Results Page

Create a custom page template (`page-flight-results.php`):

```php
<?php
/**
 * Template Name: Flight Results
 */
get_header();
?>

<div class="custom-flight-results">
  <h1>Flight Search Results</h1>

  <!-- Search summary -->
  <div id="flight-search-params"></div>

  <!-- Results container -->
  <div id="flight-results"></div>
</div>

<script>
// Custom results handler
window.addEventListener('flightResultsLoaded', function(event) {
  const data = event.detail.data;
  const container = document.getElementById('flight-results');

  // Your custom results rendering logic
  container.innerHTML = `
    <div class="my-custom-flights">
      <h2>Available Flights</h2>
      <!-- Build your custom UI here -->
    </div>
  `;
});
</script>

<?php
// Load the results script
echo do_shortcode('[alibeyg_flight_results]');

get_footer();
?>
```

---

## Testing Checklist

- [ ] Search widget redirects to correct URL
- [ ] Search parameters display correctly on results page
- [ ] Loading state appears
- [ ] API call succeeds (check Network tab)
- [ ] Results display after loading
- [ ] Search again with different parameters works correctly
- [ ] Direct navigation to `/flight/` shows "no search" message

---

## Support

If you're still having issues:

1. **Check WordPress error logs** for API errors
2. **Check browser console** for JavaScript errors
3. **Verify plugin version** is 0.5.1 or higher
4. **Test the API directly**:
   ```bash
   curl -X POST https://your-site.com/wp-json/alibeyg/v1/flight-search \
     -H "Content-Type: application/json" \
     -d '{"Lang":"EN","TravelerInfoSummary":{"AirTravelerAvail":{"PassengerTypeQuantity":[{"Code":"ADT","Quantity":1}]}},"OriginDestinationInformations":[{"OriginLocation":{"CodeContext":"IATA","LocationCode":"NJF","MultiAirportCityInd":false},"DestinationLocation":{"CodeContext":"IATA","LocationCode":"DXB","MultiAirportCityInd":false},"DepartureDateTime":"2025-12-01","ArrivalDateTime":null}]}'
   ```
