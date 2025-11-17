/**
 * Travel Widget JavaScript
 * Alibeyg Citynet Bridge Plugin
 *
 * This file contains all the JavaScript logic for the travel booking widget
 * Handles: Tab switching, form validation, autocomplete, passenger selection, and search submissions
 */

(function() {
  'use strict';

  // Prevent multiple initializations
  if (window.__abg_citynet_widget) return;
  window.__abg_citynet_widget = true;

  /**
   * Initialize widget when DOM is ready
   */
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  /**
   * Main widget initialization
   */
  ready(function() {
    var host = document.getElementById('adivaha-wrapper');
    if (!host) return;

    // Get configuration from data attributes
    var cfg = {
      primaryColor: host.getAttribute('data-primary') || '#B8011F',
      primaryHover: host.getAttribute('data-primary-hover') || '#9a0119',
      flightSearchUrl: host.getAttribute('data-flight-url') || '/flights-search/',
      hotelSearchUrl: host.getAttribute('data-hotel-url') || '/hotels-search/',
      visaSearchUrl: host.getAttribute('data-visa-url') || '/visa-search/'
    };

    // Localized strings (injected via wp_localize_script)
    var T = window.ABG_TR || {};

    // Apply custom colors
    host.style.setProperty('--primary-color', cfg.primaryColor);
    host.style.setProperty('--primary-hover', cfg.primaryHover);

    // Get widget container
    var root = host.querySelector('.travel-widget-container');
    if (!root) return;

    // =========================================================================
    // TAB SWITCHING
    // =========================================================================

    root.querySelectorAll('.tw-tab').forEach(function(btn) {
      btn.addEventListener('click', function() {
        root.querySelectorAll('.tw-tab').forEach(function(b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');

        var tab = btn.getAttribute('data-tab');
        root.querySelectorAll('.tw-tab-panel').forEach(function(p) {
          p.classList.remove('active');
        });
        root.querySelector('#' + tab + '-panel').classList.add('active');
      });
    });

    // =========================================================================
    // TRIP TYPE TOGGLE (One-way vs Round-trip)
    // =========================================================================

    root.querySelectorAll('input[name="tripType"]').forEach(function(r) {
      r.addEventListener('change', function() {
        var ret = root.querySelector('#returnDateField');
        ret.style.display = (r.value === 'one-way') ? 'none' : 'block';
      });
    });

    // =========================================================================
    // DATE INPUTS - Set minimum date to today
    // =========================================================================

    var today = new Date().toISOString().split('T')[0];
    ['departDate', 'returnDate', 'checkIn', 'checkOut'].forEach(function(id) {
      var el = root.querySelector('#' + id);
      if (el) el.setAttribute('min', today);
    });

    // =========================================================================
    // PASSENGER DROPDOWN (Flights)
    // =========================================================================

    var pax = { adults: 1, children: 0, infants: 0 };
    var disp = root.querySelector('#passengerDisplay');
    var dd = root.querySelector('#passengerDropdown');
    var hA = root.querySelector('#hidAdults');
    var hC = root.querySelector('#hidChildren');
    var hI = root.querySelector('#hidInfants');
    var hCls = root.querySelector('#flightClass');
    var clsMirror = root.querySelector('#flightClassMirror');

    function updatePassengerDisplay() {
      var total = pax.adults + pax.children + pax.infants;
      var text = total + ' ' + (total === 1 ? (T.Passenger || 'Passenger') : (T.Passengers_lc || 'Passengers'));
      var clsLabel = clsMirror.options[clsMirror.selectedIndex].text || (T.Economy || 'Economy');
      disp.value = text + ', ' + clsLabel;
      hA.value = pax.adults;
      hC.value = pax.children;
      hI.value = pax.infants;
      root.querySelector('#adultsCount').textContent = pax.adults;
      root.querySelector('#childrenCount').textContent = pax.children;
      root.querySelector('#infantsCount').textContent = pax.infants;
    }

    function clamp() {
      pax.adults = Math.max(1, Math.min(9, pax.adults));
      pax.children = Math.max(0, Math.min(9, pax.children));
      pax.infants = Math.max(0, Math.min(pax.adults, pax.infants)); // infants <= adults
    }

    // Toggle dropdown
    disp.addEventListener('click', function(e) {
      e.stopPropagation();
      dd.classList.toggle('show');
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
      if (!dd.contains(e.target) && e.target !== disp) {
        dd.classList.remove('show');
      }
    });

    // Prevent closing when clicking inside
    dd.addEventListener('click', function(e) {
      e.stopPropagation();
    });

    // Counter buttons
    root.querySelector('#adultsInc').addEventListener('click', function() {
      pax.adults++;
      clamp();
      updatePassengerDisplay();
    });
    root.querySelector('#adultsDec').addEventListener('click', function() {
      pax.adults--;
      clamp();
      updatePassengerDisplay();
    });
    root.querySelector('#childrenInc').addEventListener('click', function() {
      pax.children++;
      clamp();
      updatePassengerDisplay();
    });
    root.querySelector('#childrenDec').addEventListener('click', function() {
      pax.children--;
      clamp();
      updatePassengerDisplay();
    });
    root.querySelector('#infantsInc').addEventListener('click', function() {
      pax.infants++;
      clamp();
      updatePassengerDisplay();
    });
    root.querySelector('#infantsDec').addEventListener('click', function() {
      pax.infants--;
      clamp();
      updatePassengerDisplay();
    });

    // Class radio buttons in dropdown
    dd.querySelectorAll('input[name="flightClassDropdown"]').forEach(function(r) {
      r.addEventListener('change', function() {
        hCls.value = r.value;
        // Sync mirror select
        for (var i = 0; i < clsMirror.options.length; i++) {
          clsMirror.options[i].selected = (clsMirror.options[i].value === r.value);
        }
        updatePassengerDisplay();
      });
    });

    // Sync when class mirror changes directly
    clsMirror.addEventListener('change', function() {
      hCls.value = clsMirror.value;
      // Update radio in dropdown
      dd.querySelectorAll('input[name="flightClassDropdown"]').forEach(function(r) {
        r.checked = (r.value === clsMirror.value);
      });
      updatePassengerDisplay();
    });

    // Done button
    root.querySelector('#passengersDone').addEventListener('click', function() {
      dd.classList.remove('show');
    });

    clamp();
    updatePassengerDisplay();

    // =========================================================================
    // VISA: COUNTRY SELECTOR WITH SEARCH
    // =========================================================================

    var countries = [
      {code:'US',name:'United States'},{code:'GB',name:'United Kingdom'},{code:'CA',name:'Canada'},
      {code:'AU',name:'Australia'},{code:'DE',name:'Germany'},{code:'FR',name:'France'},
      {code:'IT',name:'Italy'},{code:'ES',name:'Spain'},{code:'NL',name:'Netherlands'},
      {code:'CH',name:'Switzerland'},{code:'SE',name:'Sweden'},{code:'NO',name:'Norway'},
      {code:'DK',name:'Denmark'},{code:'FI',name:'Finland'},{code:'BE',name:'Belgium'},
      {code:'AT',name:'Austria'},{code:'IE',name:'Ireland'},{code:'PT',name:'Portugal'},
      {code:'GR',name:'Greece'},{code:'PL',name:'Poland'},{code:'CZ',name:'Czech Republic'},
      {code:'HU',name:'Hungary'},{code:'RO',name:'Romania'},{code:'BG',name:'Bulgaria'},
      {code:'HR',name:'Croatia'},{code:'SI',name:'Slovenia'},{code:'SK',name:'Slovakia'},
      {code:'LT',name:'Lithuania'},{code:'LV',name:'Latvia'},{code:'EE',name:'Estonia'},
      {code:'JP',name:'Japan'},{code:'CN',name:'China'},{code:'KR',name:'South Korea'},
      {code:'IN',name:'India'},{code:'SG',name:'Singapore'},{code:'MY',name:'Malaysia'},
      {code:'TH',name:'Thailand'},{code:'ID',name:'Indonesia'},{code:'PH',name:'Philippines'},
      {code:'VN',name:'Vietnam'},{code:'AE',name:'United Arab Emirates'},{code:'SA',name:'Saudi Arabia'},
      {code:'QA',name:'Qatar'},{code:'KW',name:'Kuwait'},{code:'OM',name:'Oman'},
      {code:'BH',name:'Bahrain'},{code:'JO',name:'Jordan'},{code:'LB',name:'Lebanon'},
      {code:'EG',name:'Egypt'},{code:'TR',name:'Turkey'},{code:'IL',name:'Israel'},
      {code:'ZA',name:'South Africa'},{code:'KE',name:'Kenya'},{code:'NG',name:'Nigeria'},
      {code:'MA',name:'Morocco'},{code:'TN',name:'Tunisia'},{code:'DZ',name:'Algeria'},
      {code:'BR',name:'Brazil'},{code:'AR',name:'Argentina'},{code:'CL',name:'Chile'},
      {code:'CO',name:'Colombia'},{code:'PE',name:'Peru'},{code:'MX',name:'Mexico'},
      {code:'NZ',name:'New Zealand'},{code:'RU',name:'Russia'},{code:'UA',name:'Ukraine'}
    ];

    var countryDisp = root.querySelector('#visaCountryDisplay');
    var countryCode = root.querySelector('#visaCountryCode');
    var countryDD = root.querySelector('#countryDropdown');
    var countrySearch = root.querySelector('#countrySearchInput');
    var countryList = root.querySelector('#countryList');

    function renderCountries(filter) {
      var filtered = filter ? countries.filter(function(c) {
        return c.name.toLowerCase().indexOf(filter.toLowerCase()) > -1;
      }) : countries;

      if (!filtered.length) {
        countryList.innerHTML = '<div class="tw-country-item no-results">' + (T.NoResults || 'No results') + '</div>';
        return;
      }

      countryList.innerHTML = filtered.map(function(c) {
        return '<div class="tw-country-item" data-code="' + c.code + '" data-name="' + c.name + '">' + c.name + '</div>';
      }).join('');

      countryList.querySelectorAll('.tw-country-item:not(.no-results)').forEach(function(item) {
        item.addEventListener('click', function() {
          countryDisp.value = item.getAttribute('data-name');
          countryCode.value = item.getAttribute('data-code');
          countryDD.classList.remove('show');
          countrySearch.value = '';
        });
      });
    }

    countryDisp.addEventListener('click', function(e) {
      e.stopPropagation();
      countryDD.classList.toggle('show');
      if (countryDD.classList.contains('show')) {
        renderCountries('');
        countrySearch.focus();
      }
    });

    countrySearch.addEventListener('input', function() {
      renderCountries(countrySearch.value);
    });

    document.addEventListener('click', function(e) {
      if (!countryDD.contains(e.target) && e.target !== countryDisp) {
        countryDD.classList.remove('show');
      }
    });

    countryDD.addEventListener('click', function(e) {
      e.stopPropagation();
    });

    // =========================================================================
    // VISA: PERSON DROPDOWN
    // =========================================================================

    var visaPax = { adults: 1, children: 0 };
    var visaPDisp = root.querySelector('#visaPersonDisplay');
    var visaPDD = root.querySelector('#personDropdown');
    var visaHAdults = root.querySelector('#hidVisaAdults');
    var visaHChildren = root.querySelector('#hidVisaChildren');

    function updateVisaPersonDisplay() {
      var total = visaPax.adults + visaPax.children;
      var text = total + ' ' + (total === 1 ? (T.Person || 'Person') : (T.Persons || 'Persons'));
      visaPDisp.value = text;
      visaHAdults.value = visaPax.adults;
      visaHChildren.value = visaPax.children;
      root.querySelector('#visaAdultsCount').textContent = visaPax.adults;
      root.querySelector('#visaChildrenCount').textContent = visaPax.children;
    }

    function clampVisa() {
      visaPax.adults = Math.max(1, Math.min(9, visaPax.adults));
      visaPax.children = Math.max(0, Math.min(9, visaPax.children));
    }

    visaPDisp.addEventListener('click', function(e) {
      e.stopPropagation();
      visaPDD.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
      if (!visaPDD.contains(e.target) && e.target !== visaPDisp) {
        visaPDD.classList.remove('show');
      }
    });

    visaPDD.addEventListener('click', function(e) {
      e.stopPropagation();
    });

    root.querySelector('#visaAdultsInc').addEventListener('click', function() {
      visaPax.adults++;
      clampVisa();
      updateVisaPersonDisplay();
    });
    root.querySelector('#visaAdultsDec').addEventListener('click', function() {
      visaPax.adults--;
      clampVisa();
      updateVisaPersonDisplay();
    });
    root.querySelector('#visaChildrenInc').addEventListener('click', function() {
      visaPax.children++;
      clampVisa();
      updateVisaPersonDisplay();
    });
    root.querySelector('#visaChildrenDec').addEventListener('click', function() {
      visaPax.children--;
      clampVisa();
      updateVisaPersonDisplay();
    });
    root.querySelector('#visaPersonDone').addEventListener('click', function() {
      visaPDD.classList.remove('show');
    });

    clampVisa();
    updateVisaPersonDisplay();

    // =========================================================================
    // AUTOCOMPLETE FOR FROM/TO (Flights)
    // =========================================================================

    /**
     * Fetch airport suggestions from API
     */
    function fetchAirports(term) {
      if (!term || term.trim().length < 2) return Promise.resolve([]);
      var url = '/wp-json/alibeyg/v1/places?term=' + encodeURIComponent(term.trim()) + '&limit=7&locale=' + (document.documentElement.lang || 'en');
      return fetch(url)
        .then(function(r) { if (!r.ok) return []; return r.json(); })
        .then(function(j) { return (j && j.airports) ? j.airports : []; })
        .catch(function() { return []; });
    }

    /**
     * Mount autocomplete on input field
     */
    function mountSuggest(inputId, listId) {
      var input = root.querySelector('#' + inputId);
      var list = root.querySelector('#' + listId);
      var timer = null;

      function hide() {
        list.style.display = 'none';
        list.innerHTML = '';
      }

      function show(items) {
        if (!items.length) {
          hide();
          return;
        }
        list.innerHTML = items.map(function(a) {
          var label = (a.city_fullname || a.fullname || '') + ' — <span class="tw-code">' + (a.code || '') + '</span>';
          return '<div class="tw-suggest-item" data-code="' + (a.code || '') + '" data-label="' + (a.fullname || '') + '">' + label + '</div>';
        }).join('');
        list.style.display = 'block';
        list.querySelectorAll('.tw-suggest-item').forEach(function(it) {
          it.addEventListener('click', function(e) {
            e.preventDefault();
            input.value = it.getAttribute('data-code') || '';
            hide();
          });
        });
      }

      input.addEventListener('input', function() {
        clearTimeout(timer);
        var q = input.value;
        if (!q || q.length < 2) {
          hide();
          return;
        }
        timer = setTimeout(function() {
          fetchAirports(q).then(show).catch(hide);
        }, 180);
      });

      input.addEventListener('blur', function() {
        setTimeout(hide, 150);
      });

      input.addEventListener('focus', function() {
        if (input.value && input.value.length >= 2) {
          fetchAirports(input.value).then(show).catch(hide);
        }
      });
    }

    mountSuggest('flightFrom', 'fromSuggest');
    mountSuggest('flightTo', 'toSuggest');

    // =========================================================================
    // SEARCH HANDLERS
    // =========================================================================

    /**
     * Search Flights
     */
    function doSearchFlights() {
      var from = (root.querySelector('#flightFrom').value || '').trim();
      var to = (root.querySelector('#flightTo').value || '').trim();
      var depart = root.querySelector('#departDate').value;
      var ret = root.querySelector('#returnDate').value;
      var trip = root.querySelector('input[name="tripType"]:checked').value;
      var fclass = hCls.value;

      // Get service checkboxes
      var cipChecked = root.querySelector('#cipService').checked;
      var insuranceChecked = root.querySelector('#insuranceService').checked;

      if (!from || !to || !depart) {
        alert(T.PleaseFillRequired || 'Please fill in all required fields');
        return;
      }
      if (trip === 'round-trip' && !ret) {
        alert(T.PleaseSelectReturn || 'Please select a return date');
        return;
      }

      // Map class names to API format
      var cabinMap = {
        'economy': 'Economy',
        'premium-economy': 'PremiumEconomy',
        'business': 'Business',
        'first': 'First'
      };

      // Build passenger array - API requires exactly 3 items (ADT, CHD, INF)
      var passengers = [
        {"Code": "ADT", "Quantity": pax.adults},
        {"Code": "CHD", "Quantity": pax.children},
        {"Code": "INF", "Quantity": pax.infants}
      ];

      // Build origin-destination array
      var originDest = [{
        "OriginLocation": {
          "CodeContext": "IATA",
          "LocationCode": from,
          "MultiAirportCityInd": false
        },
        "DestinationLocation": {
          "CodeContext": "IATA",
          "LocationCode": to,
          "MultiAirportCityInd": false
        },
        "DepartureDateTime": depart,
        "ArrivalDateTime": null
      }];

      // Add return leg for round-trip
      if (trip === 'round-trip' && ret) {
        originDest.push({
          "OriginLocation": {
            "CodeContext": "IATA",
            "LocationCode": to,
            "MultiAirportCityInd": false
          },
          "DestinationLocation": {
            "CodeContext": "IATA",
            "LocationCode": from,
            "MultiAirportCityInd": false
          },
          "DepartureDateTime": ret,
          "ArrivalDateTime": null
        });
      }

      // Prepare search payload in exact API format
      var payload = {
        "Lang": "FA",
        "TravelPreference": {
          "CabinPref": {
            "Cabin": cabinMap[fclass] || "Economy"
          },
          "EquipPref": {
            "AirEquipType": "IATA"
          },
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
            "PassengerTypeQuantity": passengers
          }
        },
        "SpecificFlightInfo": {
          "Airline": []
        },
        "OriginDestinationInformations": originDest,
        "DeepLink": 0
      };

      // Add CIP and Insurance to payload if checked
      if (cipChecked) payload.CIP = true;
      if (insuranceChecked) payload.Insurance = true;

      // Store in sessionStorage for the target page to use
      try {
        sessionStorage.setItem('flightSearchPayload', JSON.stringify(payload));
        sessionStorage.setItem('flightSearchParams', JSON.stringify({
          origin: from,
          destination: to,
          departureDate: depart,
          returnDate: ret,
          adults: pax.adults,
          children: pax.children,
          infants: pax.infants,
          class: fclass,
          cabin: cabinMap[fclass] || "Economy",
          tripType: trip,
          cip: cipChecked,
          insurance: insuranceChecked
        }));
        sessionStorage.setItem('autoSearch', 'true');
      } catch (e) {
        console.error('SessionStorage error:', e);
      }

      // Build URL with all parameters (base64 encoded payload)
      var params = [];
      params.push('from=' + encodeURIComponent(from));
      params.push('to=' + encodeURIComponent(to));
      params.push('depart=' + encodeURIComponent(depart));
      if (ret) params.push('return=' + encodeURIComponent(ret));
      params.push('adults=' + encodeURIComponent(pax.adults));
      params.push('children=' + encodeURIComponent(pax.children));
      params.push('infants=' + encodeURIComponent(pax.infants));
      params.push('cabin=' + encodeURIComponent(cabinMap[fclass] || "Economy"));
      params.push('type=' + encodeURIComponent(trip === 'round-trip' ? 'twoWay' : 'oneWay'));
      if (cipChecked) params.push('cip=1');
      if (insuranceChecked) params.push('insurance=1');

      // Add encoded payload
      params.push('searchPayload=' + encodeURIComponent(btoa(JSON.stringify(payload))));

      console.log('Redirecting to:', cfg.flightSearchUrl + '?' + params.join('&'));
      console.log('Payload stored in sessionStorage');

      // Redirect
      window.location.href = cfg.flightSearchUrl + '?' + params.join('&');
    }

    /**
     * Search Hotels
     */
    function doSearchHotels() {
      var dest = (root.querySelector('#hotelDestination').value || '').trim();
      var ci = root.querySelector('#checkIn').value;
      var co = root.querySelector('#checkOut').value;
      var rooms = root.querySelector('#rooms').value;
      var guests = root.querySelector('#guests').value;

      if (!dest || !ci || !co) {
        alert(T.PleaseFillRequired || 'Please fill in all required fields');
        return;
      }

      var searchBtn = root.querySelector('#btnSearchHotels');
      var originalText = searchBtn.innerHTML;
      searchBtn.innerHTML = '<svg class="tw-icon animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> ' + (T.SearchHotels || 'Search Hotels') + '...';
      searchBtn.disabled = true;

      var searchData = {
        path: 'hotels/search',
        method: 'POST',
        payload: {
          destination: dest,
          checkIn: ci,
          checkOut: co,
          rooms: parseInt(rooms),
          guests: parseInt(guests)
        }
      };

      fetch('/wp-json/alibeyg/v1/proxy', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(searchData)
      })
      .then(function(response) { return response.json(); })
      .then(function(result) {
        searchBtn.innerHTML = originalText;
        searchBtn.disabled = false;

        if (result.status === 200 && result.data) {
          var params = [];
          params.push('destination=' + encodeURIComponent(dest));
          params.push('checkin=' + encodeURIComponent(ci));
          params.push('checkout=' + encodeURIComponent(co));
          params.push('rooms=' + encodeURIComponent(rooms));
          params.push('guests=' + encodeURIComponent(guests));

          try {
            sessionStorage.setItem('hotelSearchResults', JSON.stringify(result.data));
          } catch (e) {}

          window.location.href = cfg.hotelSearchUrl + '?' + params.join('&');
        } else {
          alert('Search failed. Please try again.');
        }
      })
      .catch(function(error) {
        searchBtn.innerHTML = originalText;
        searchBtn.disabled = false;
        console.error('Search error:', error);
        alert('Search failed. Please try again.');
      });
    }

    /**
     * Search Visa
     */
    function doSearchVisa() {
      var country = countryCode.value;
      var countryName = countryDisp.value;
      var duration = root.querySelector('#visaDuration').value;

      if (!country || !countryName) {
        alert(T.PleaseFillRequired || 'Please fill in all required fields');
        return;
      }

      var searchBtn = root.querySelector('#btnSearchVisa');
      var originalText = searchBtn.innerHTML;
      searchBtn.innerHTML = '<svg class="tw-icon animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> ' + (T.SearchCIP || 'Search CIP') + '...';
      searchBtn.disabled = true;

      var searchData = {
        path: 'visa/search',
        method: 'POST',
        payload: {
          country: country,
          countryName: countryName,
          duration: parseInt(duration),
          adults: visaPax.adults,
          children: visaPax.children
        }
      };

      fetch('/wp-json/alibeyg/v1/proxy', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(searchData)
      })
      .then(function(response) { return response.json(); })
      .then(function(result) {
        searchBtn.innerHTML = originalText;
        searchBtn.disabled = false;

        if (result.status === 200 && result.data) {
          var params = [];
          params.push('country=' + encodeURIComponent(country));
          params.push('countryName=' + encodeURIComponent(countryName));
          params.push('duration=' + encodeURIComponent(duration));
          params.push('adults=' + encodeURIComponent(visaPax.adults));
          params.push('children=' + encodeURIComponent(visaPax.children));

          try {
            sessionStorage.setItem('visaSearchResults', JSON.stringify(result.data));
          } catch (e) {}

          window.location.href = cfg.visaSearchUrl + '?' + params.join('&');
        } else {
          alert('Search failed. Please try again.');
        }
      })
      .catch(function(error) {
        searchBtn.innerHTML = originalText;
        searchBtn.disabled = false;
        console.error('Search error:', error);
        alert('Search failed. Please try again.');
      });
    }

    // Attach event listeners
    root.querySelector('#btnSearchFlights').addEventListener('click', function(e) {
      e.preventDefault();
      doSearchFlights();
    });
    root.querySelector('#btnSearchHotels').addEventListener('click', function(e) {
      e.preventDefault();
      doSearchHotels();
    });
    root.querySelector('#btnSearchVisa').addEventListener('click', function(e) {
      e.preventDefault();
      doSearchVisa();
    });

    // Provide globals (for backward compatibility)
    window.searchFlights = doSearchFlights;
    window.searchHotels = doSearchHotels;
    window.searchVisa = doSearchVisa;
  });
})();
