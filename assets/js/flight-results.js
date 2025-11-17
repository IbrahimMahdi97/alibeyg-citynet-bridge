/**
 * Flight Results Page Helper
 *
 * This script should be included on the flight results page (e.g., /flight/)
 * It reads search parameters from sessionStorage/URL and triggers the search
 *
 * @package Alibeyg_Citynet_Bridge
 * @since 0.5.1
 */

(function() {
  'use strict';

  // Prevent multiple initializations
  if (window.AlibetgFlightResults) return;

  /**
   * Flight Results Handler
   */
  window.AlibetgFlightResults = {

    /**
     * Get cookie value by name
     */
    getCookie: function(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) {
        return parts.pop().split(';').shift();
      }
      return null;
    },

    /**
     * Initialize the flight search on page load
     */
    init: function() {
      console.log('[Alibeyg Flight Results] Initializing...');

      // Check if auto-search is enabled
      const autoSearch = sessionStorage.getItem('autoSearch');

      if (autoSearch === 'true') {
        console.log('[Alibeyg Flight Results] Auto-search enabled, loading results...');
        this.performSearch();
      } else {
        console.log('[Alibeyg Flight Results] No auto-search flag found');
        // Try to read from URL parameters as fallback
        this.loadFromURLParams();
      }
    },

    /**
     * Perform flight search using stored payload
     */
    performSearch: function() {
      // Get the stored payload
      const payloadStr = sessionStorage.getItem('flightSearchPayload');
      const paramsStr = sessionStorage.getItem('flightSearchParams');

      if (!payloadStr) {
        console.log('[Alibeyg Flight Results] No flight search payload found - other plugins may handle search');
        // Don't show error - the citynet plugin might handle this
        this.hideLoading();
        return;
      }

      try {
        const payload = JSON.parse(payloadStr);
        const params = paramsStr ? JSON.parse(paramsStr) : null;

        console.log('[Alibeyg Flight Results] Search payload:', payload);
        console.log('[Alibeyg Flight Results] Search params:', params);

        // Display search parameters to user
        this.displaySearchParams(params);

        // Show loading state
        this.showLoading();

        // Make API call
        this.callFlightSearchAPI(payload);

      } catch (e) {
        console.error('[Alibeyg Flight Results] Error parsing stored data:', e);
        this.showError('Error loading search parameters. Please search again.');
      }
    },

    /**
     * Load search parameters from URL if sessionStorage is empty
     */
    loadFromURLParams: function() {
      const urlParams = new URLSearchParams(window.location.search);
      const encodedPayload = urlParams.get('searchPayload');

      if (encodedPayload) {
        try {
          const payload = JSON.parse(atob(decodeURIComponent(encodedPayload)));
          console.log('[Alibeyg Flight Results] Loaded payload from URL:', payload);

          // Build params object from URL
          const params = {
            origin: urlParams.get('from') || '',
            destination: urlParams.get('to') || '',
            departureDate: urlParams.get('depart') || '',
            returnDate: urlParams.get('return') || '',
            adults: parseInt(urlParams.get('adults')) || 1,
            children: parseInt(urlParams.get('children')) || 0,
            infants: parseInt(urlParams.get('infants')) || 0,
            cabin: urlParams.get('cabin') || 'Economy',
            tripType: urlParams.get('type') === 'oneWay' ? 'one-way' : 'round-trip',
            cip: urlParams.get('cip') === '1',
            insurance: urlParams.get('insurance') === '1'
          };

          this.displaySearchParams(params);
          this.showLoading();
          this.callFlightSearchAPI(payload);

        } catch (e) {
          console.error('[Alibeyg Flight Results] Error parsing URL payload:', e);
          // Don't show error - let other plugins handle it
          this.hideLoading();
        }
      } else {
        console.log('[Alibeyg Flight Results] No URL parameters found - other plugins may handle search');
        this.hideLoading();
      }
    },

    /**
     * Call the flight search API
     */
    callFlightSearchAPI: function(payload) {
      const apiUrl = '/wp-json/alibeyg/v1/flight-search';

      console.log('[Alibeyg Flight Results] Calling API:', apiUrl);
      console.log('[Alibeyg Flight Results] Payload:', JSON.stringify(payload, null, 2));

      // Get auth token from cookie
      const authToken = this.getCookie('auth_token');

      // Build headers
      const headers = {
        'Content-Type': 'application/json'
      };

      // Add Authorization header if token exists
      if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
        console.log('[Alibeyg Flight Results] Auth token found, adding to request');
      } else {
        console.warn('[Alibeyg Flight Results] No auth_token cookie found');
      }

      fetch(apiUrl, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(payload)
      })
      .then(response => {
        console.log('[Alibeyg Flight Results] API Response status:', response.status);
        return response.json();
      })
      .then(result => {
        console.log('[Alibeyg Flight Results] API Result:', result);

        // Clear the auto-search flag
        sessionStorage.removeItem('autoSearch');

        if (result.status === 200 && result.data) {
          this.displayResults(result.data);
        } else if (result.code && result.message) {
          // WordPress error format
          this.showError('Search failed: ' + result.message);
        } else {
          this.showError('Search failed. Please try again.');
        }
      })
      .catch(error => {
        console.error('[Alibeyg Flight Results] API Error:', error);
        sessionStorage.removeItem('autoSearch');
        this.showError('Network error. Please check your connection and try again.');
      });
    },

    /**
     * Display search parameters to user
     */
    displaySearchParams: function(params) {
      if (!params) return;

      const container = document.getElementById('flight-search-params');
      if (!container) {
        console.warn('[Alibeyg Flight Results] #flight-search-params element not found');
        return;
      }

      const totalPassengers = (params.adults || 0) + (params.children || 0) + (params.infants || 0);
      const tripTypeLabel = params.tripType === 'one-way' ? 'One Way' : 'Round Trip';

      let html = `
        <div class="search-summary">
          <h3>Your Search</h3>
          <div class="search-details">
            <span class="route">${params.origin} → ${params.destination}</span>
            ${params.returnDate ? `<span class="route-return">${params.destination} → ${params.origin}</span>` : ''}
            <span class="dates">
              ${params.departureDate}
              ${params.returnDate ? ' - ' + params.returnDate : ''}
            </span>
            <span class="passengers">${totalPassengers} Passenger${totalPassengers !== 1 ? 's' : ''}</span>
            <span class="cabin">${params.cabin}</span>
            <span class="trip-type">${tripTypeLabel}</span>
          </div>
        </div>
      `;

      container.innerHTML = html;
    },

    /**
     * Show loading state
     */
    showLoading: function() {
      const container = document.getElementById('flight-results');
      if (!container) {
        console.warn('[Alibeyg Flight Results] #flight-results element not found');
        return;
      }

      container.innerHTML = `
        <div class="loading-state">
          <div class="spinner"></div>
          <p>Searching for flights...</p>
        </div>
      `;
    },

    /**
     * Hide loading state
     */
    hideLoading: function() {
      const container = document.getElementById('flight-results');
      if (container) {
        const loadingState = container.querySelector('.loading-state');
        if (loadingState) {
          loadingState.remove();
        }
      }
    },

    /**
     * Display flight results
     */
    displayResults: function(data) {
      console.log('[Alibeyg Flight Results] Displaying results:', data);

      // Trigger custom event with results data
      const event = new CustomEvent('flightResultsLoaded', {
        detail: { data: data }
      });
      window.dispatchEvent(event);

      // Also make data available globally for other plugins
      window.alibeyg_flight_data = data;

      // Check if citynet plugin wants to handle display
      if (window.citynetHandleResults && typeof window.citynetHandleResults === 'function') {
        console.log('[Alibeyg Flight Results] Passing data to citynet plugin');
        window.citynetHandleResults(data);
        return;
      }

      // You can also update a container directly
      const container = document.getElementById('flight-results');
      if (container) {
        // This is a basic display - you should customize this based on your needs
        container.innerHTML = `
          <div class="results-container">
            <p class="results-info">Results loaded successfully!</p>
            <p>Data is now available for the citynet plugin to display.</p>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; max-height: 400px;">
              ${JSON.stringify(data, null, 2)}
            </pre>
          </div>
        `;
      }
    },

    /**
     * Show error message
     */
    showError: function(message) {
      const container = document.getElementById('flight-results');
      if (container) {
        container.innerHTML = `
          <div class="error-state">
            <p class="error-message">${message}</p>
            <button onclick="window.history.back()">Go Back</button>
          </div>
        `;
      }
    },

    /**
     * Show "no search" state
     */
    showNoSearch: function() {
      const container = document.getElementById('flight-results');
      if (container) {
        // Just hide loading, don't show error - citynet plugin might handle it
        this.hideLoading();
      }
    }
  };

  // Auto-initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      window.AlibetgFlightResults.init();
    });
  } else {
    // DOM already loaded
    window.AlibetgFlightResults.init();
  }

})();
