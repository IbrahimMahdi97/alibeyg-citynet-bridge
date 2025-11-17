# Flight Results Page Setup - Working with Existing Citynet Plugin

## Your Current Setup

You already have the `[citynet]` shortcode on your `/flight/` page that displays results from a custom citynet plugin. You want to **keep that** and **add** our search functionality to it.

---

## Quick Setup (Keep Existing Plugin)

### Step 1: Edit Your Flight Results Page

1. Go to **WordPress Admin → Pages**
2. Find and edit the page at `/flight/`
3. **Add this at the TOP** of the page (before `[citynet]`):

#### Option A: Show Search Summary + Citynet Results
```
[alibeyg_flight_results mode="params-only"]

[citynet]
```

#### Option B: Fetch Data Silently (Recommended)
```
[alibeyg_flight_results mode="silent"]

[citynet]
```

#### Option C: Show Everything
```
[alibeyg_flight_results]

[citynet]
```

4. Click **Update**

Your page should now have **BOTH** shortcodes.

---

## Shortcode Modes

Our shortcode supports three modes:

| Mode | Usage | What It Does |
|------|-------|--------------|
| **`silent`** | `[alibeyg_flight_results mode="silent"]` | Fetches data invisibly, citynet displays results |
| **`params-only`** | `[alibeyg_flight_results mode="params-only"]` | Shows search summary only, citynet displays results |
| **`full`** | `[alibeyg_flight_results]` (default) | Shows search summary AND results |

---

## How It Works

### Dual-Plugin Architecture

```
User searches (NJF → THR, 2 adults)
         ↓
[alibeyg_flight_results] reads parameters
         ↓
Calls API: /wp-json/alibeyg/v1/flight-search
         ↓
Stores data in: window.alibeyg_flight_data
         ↓
[citynet] plugin can access this data
         ↓
Both plugins display results!
```

### Data Flow

1. **User searches** using the travel widget
2. **Alibeyg plugin**:
   - Reads search parameters (NJF → THR, 2 adults)
   - Calls the API with correct parameters
   - Stores results in `window.alibeyg_flight_data`
   - Triggers `flightResultsLoaded` event
3. **Citynet plugin**:
   - Can access `window.alibeyg_flight_data`
   - Can listen for `flightResultsLoaded` event
   - Displays results in its own format

---

## Integration Options

### Option 1: Citynet Plugin Uses Our Data (Recommended)

If your citynet plugin can read data from JavaScript, add this to your citynet plugin:

```javascript
// Listen for our API results
window.addEventListener('flightResultsLoaded', function(event) {
  const flightData = event.detail.data;

  // Pass to citynet's display function
  if (window.citynetHandleResults) {
    citynetHandleResults(flightData);
  }
});

// Or access data directly
if (window.alibeyg_flight_data) {
  const data = window.alibeyg_flight_data;
  // Use this data in citynet plugin
}
```

### Option 2: Hide Our Results, Show Only Citynet

Add to your page or theme CSS:

```css
/* Hide our default results display */
#flight-results {
  display: none;
}

/* Only show search parameters */
#flight-search-params {
  display: block;
}
```

### Option 3: Show Both (Side by Side)

```css
.alibeyg-flight-results-container {
  width: 30%;
  float: left;
}

.citynet-results {
  width: 65%;
  float: right;
}
```

---

## Callback Function for Citynet Plugin

If your citynet plugin needs a callback function, add this to the citynet plugin code:

```javascript
// In your citynet plugin's JavaScript
window.citynetHandleResults = function(flightData) {
  console.log('Citynet received flight data:', flightData);

  // Your citynet plugin's display logic here
  // Example:
  const container = document.querySelector('.citynet-results');
  if (container && flightData) {
    // Build your UI using flightData
    container.innerHTML = buildCitynetFlightsHTML(flightData);
  }
};
```

Our plugin will automatically detect this function and pass data to it!

---

## Page Layout Examples

### Example 1: Search Summary + Citynet Results

```
[alibeyg_flight_results]

[citynet]
```

**Result**: Shows search parameters at top, citynet plugin displays results below.

### Example 2: Hidden Alibeyg, Only Citynet

```
[alibeyg_flight_results]

<style>
#flight-results { display: none; }
</style>

[citynet]
```

**Result**: Alibeyg fetches data silently, citynet displays it.

### Example 3: Two Columns

```
<div style="display: flex; gap: 20px;">
  <div style="flex: 1;">
    [alibeyg_flight_results]
  </div>
  <div style="flex: 2;">
    [citynet]
  </div>
</div>
```

**Result**: Side-by-side display.

---

## Debugging Both Plugins

Press **F12** → **Console** to see:

### Alibeyg Plugin Logs:
```
[Alibeyg Flight Results] Initializing...
[Alibeyg Flight Results] Search payload: {origin: "NJF", destination: "THR", adults: 2}
[Alibeyg Flight Results] API Response status: 200
[Alibeyg Flight Results] Passing data to citynet plugin
```

### Check Data Availability:
```javascript
// In browser console
console.log(window.alibeyg_flight_data);
// Should show the API response
```

---

## Compatibility Features

Our plugin is designed to work alongside citynet:

1. ✅ **No Conflicts**: Uses unique IDs and class names
2. ✅ **Silent Mode**: Won't show errors if citynet handles search
3. ✅ **Data Sharing**: Makes data available via `window.alibeyg_flight_data`
4. ✅ **Event System**: Triggers `flightResultsLoaded` event
5. ✅ **Callback Support**: Detects `window.citynetHandleResults()` function

---

## Advanced: Pass Data to Citynet on Load

If citynet plugin loads before our data arrives, use this pattern:

```javascript
// Add to your theme or custom plugin
(function() {
  // Wait for our data
  window.addEventListener('flightResultsLoaded', function(event) {
    // Check if citynet is ready
    if (typeof initCitynetResults === 'function') {
      initCitynetResults(event.detail.data);
    }
  });

  // Or poll for data
  var checkData = setInterval(function() {
    if (window.alibeyg_flight_data) {
      clearInterval(checkData);
      if (typeof initCitynetResults === 'function') {
        initCitynetResults(window.alibeyg_flight_data);
      }
    }
  }, 100);
})();
```

---

## Testing Checklist

- [ ] Both shortcodes are on the page
- [ ] Search from homepage with specific parameters (e.g., NJF → THR, 2 adults)
- [ ] Check browser console for both plugins' logs
- [ ] Verify `window.alibeyg_flight_data` contains correct data
- [ ] Confirm citynet plugin receives the data
- [ ] Results display correctly

---

## Common Issues

### Issue: Citynet shows wrong search parameters

**Solution**: Make sure `[alibeyg_flight_results]` comes BEFORE `[citynet]` on the page.

### Issue: Two loading spinners appear

**Solution**: Hide one with CSS:
```css
#flight-results .loading-state { display: none; }
```

### Issue: Data not passing to citynet

**Solution**: Check if citynet registers the callback:
```javascript
// Add this before citynet loads
window.citynetHandleResults = function(data) {
  console.log('Received data:', data);
  // Your code here
};
```

---

## Support

If you need help integrating with your specific citynet plugin:

1. Share the citynet plugin's JavaScript file
2. Let me know how citynet expects to receive data
3. I can create a custom integration script

---

## Quick Reference

### Shortcode Order
```
[alibeyg_flight_results]  ← Fetches data
[citynet]                 ← Displays data
```

### Access Data in JavaScript
```javascript
// Get the API response
const flightData = window.alibeyg_flight_data;

// Listen for new searches
window.addEventListener('flightResultsLoaded', function(event) {
  const data = event.detail.data;
});

// Provide callback for our plugin
window.citynetHandleResults = function(data) {
  // Handle data here
};
```

### Hide Our Results Display
```css
#flight-results { display: none !important; }
```
