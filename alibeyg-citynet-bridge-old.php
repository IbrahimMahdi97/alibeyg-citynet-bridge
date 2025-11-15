<?php
/**
 * Plugin Name: Alibeyg Citynet Bridge
 * Description: Server-side proxy to Citynet API (Flights/Hotels/Visa) + modern travel widget with autocomplete & i18n.
 * Version: 0.5.0
 * Author: Alibeyg
 * Text Domain: alibeyg-citynet
 */

if (!defined('ABSPATH')) exit;

/* =========================
   CONFIG (optionally set in wp-config.php)
   ========================= */
if (!defined('CN_API_BASE')) define('CN_API_BASE', 'https://citynet.ir/');
if (!defined('CN_ORG_ID'))   define('CN_ORG_ID',   12345);
if (!defined('CN_API_KEY'))  define('CN_API_KEY',  '');
if (!defined('CN_USERNAME')) define('CN_USERNAME', '');
if (!defined('CN_PASSWORD')) define('CN_PASSWORD', '');

/* =========================
   I18N - Register strings for Polylang
   ========================= */
add_action('plugins_loaded', function () {
  load_plugin_textdomain('alibeyg-citynet', false, dirname(plugin_basename(__FILE__)) . '/languages/');
  
  // Register strings with Polylang if it's active
  if (function_exists('pll_register_string')) {
    $strings = [
      'Flights', 'Hotels', 'Visa', 'Round Trip', 'One Way', 'Multi City',
      'From', 'To', 'Depart', 'Return', 'Passengers', 'Class',
      'Economy', 'Premium Economy', 'Business', 'First Class', 'Search Flights',
      'Destination', 'Check-in', 'Check-out', 'Rooms', 'Guests', 'Search Hotels',
      'City or Airport', 'City, Hotel, Place', 'Powered by Travel Booking Engine',
      'Please fill in all required fields', 'Please select a return date',
      'Adults', 'Children', 'Infants', 'Travel Class', 'Done',
      'Passenger', 'Passengers', 'CIP', 'Travel Insurance',
      'Destination Country', 'Travel Duration', 'Search Visa',
      'Search country...', 'Select destination country', 'Persons', 'Person',
      'Days', 'No results'
    ];
    
    foreach ($strings as $string) {
      pll_register_string($string, $string, 'Alibeyg Travel Widget', false);
    }
  }
});

// TEMPORARY: Force re-registration of strings (remove after use)
add_action('init', function() {
  if (function_exists('pll_register_string') && isset($_GET['reregister_strings'])) {
    $strings = [
      'Flights', 'Hotels', 'Visa', 'Round Trip', 'One Way', 'Multi City',
      'From', 'To', 'Depart', 'Return', 'Passengers', 'Class',
      'Economy', 'Premium Economy', 'Business', 'First Class', 'Search Flights',
      'Destination', 'Check-in', 'Check-out', 'Rooms', 'Guests', 'Search Hotels',
      'City or Airport', 'City, Hotel, Place', 'Powered by Travel Booking Engine',
      'Please fill in all required fields', 'Please select a return date',
      'Adults', 'Children', 'Infants', 'Travel Class', 'Done',
      'Passenger', 'Passengers', 'CIP', 'Travel Insurance',
      'Destination Country', 'Travel Duration', 'Search Visa',
      'Search country...', 'Select destination country', 'Persons', 'Person',
      'Days', 'No results'
    ];
    
    foreach ($strings as $string) {
      pll_register_string($string, $string, 'Alibeyg Travel Widget', false);
    }
    
    wp_die('<div style="padding:40px;background:#4CAF50;color:white;text-align:center;font-size:18px;font-family:Arial;">
      <h2 style="margin:0 0 10px 0;">✅ Strings Successfully Re-registered!</h2>
      <p style="margin:10px 0;">All 40+ strings have been registered with Polylang.</p>
      <p style="margin:10px 0;">Go to <strong>Languages → String translations</strong> to translate them.</p>
      <p style="margin:20px 0 0 0;"><a href="' . admin_url('admin.php?page=mlang_strings') . '" style="background:white;color:#4CAF50;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;">Go to String Translations</a></p>
      <p style="margin:20px 0 0 0;font-size:14px;opacity:0.9;">You can now remove this code from your plugin file.</p>
    </div>');
  }
}, 1);

/* =========================
   AUTH (optional token flow)
   ========================= */
function abg_cn_get_token() {
  $token = get_transient('abg_cn_token');
  if ($token) return $token;
  if (!CN_USERNAME || !CN_PASSWORD) return null; // API-key mode

  $resp = wp_remote_post(trailingslashit(CN_API_BASE) . 'auth/login', [
    'headers' => ['Content-Type' => 'application/json'],
    'body'    => wp_json_encode([
      'username' => CN_USERNAME,
      'password' => CN_PASSWORD,
      'orgId'    => CN_ORG_ID,
    ]),
    'timeout'  => 20,
  ]);

  if (is_wp_error($resp)) return null;
  $code = wp_remote_retrieve_response_code($resp);
  $body = json_decode(wp_remote_retrieve_body($resp), true);
  if ($code !== 200 || empty($body['token'])) return null;

  set_transient('abg_cn_token', $body['token'], 60 * 50);
  return $body['token'];
}

/* =========================
   PROXY CALL
   ========================= */
function abg_cn_request($method, $path, $payload = []) {
  $allowed = [
    'flights/search',
    'flights/airports/suggest',
    'hotels/search',
    'hotels/cities/suggest',
    'visa/search',
  ];
  if (!in_array($path, $allowed, true)) {
    return new WP_Error('forbidden_path', 'This path is not allowed.', ['status' => 403]);
  }

  $headers = ['Content-Type' => 'application/json'];
  if (CN_API_KEY) {
    $headers['Authorization'] = 'Bearer ' . CN_API_KEY;
  } else {
    $t = abg_cn_get_token();
    if ($t) $headers['Authorization'] = 'Bearer ' . $t;
  }

  $args = [
    'method'  => (strtoupper($method) === 'GET' ? 'GET' : 'POST'),
    'headers' => $headers,
    'timeout' => 25,
  ];

  if ($args['method'] === 'GET') {
    $url = trailingslashit(CN_API_BASE) . $path . ($payload ? '?' . http_build_query($payload) : '');
  } else {
    $url = trailingslashit(CN_API_BASE) . $path;
    $args['body'] = wp_json_encode($payload);
  }

  $resp = wp_remote_request($url, $args);
  if (is_wp_error($resp)) return $resp;

  $code = wp_remote_retrieve_response_code($resp);
  $body = json_decode(wp_remote_retrieve_body($resp), true);
  return ['status' => $code, 'data' => $body];
}

/* =========================
   REST: /wp-json/alibeyg/v1/proxy
   ========================= */
add_action('rest_api_init', function () {
  register_rest_route('alibeyg/v1', '/proxy', [
    'methods'  => 'POST',
    'callback' => function (WP_REST_Request $r) {
      $path    = sanitize_text_field($r->get_param('path'));
      $method  = $r->get_param('method') ?: 'POST';
      $payload = $r->get_param('payload');
      if (!is_array($payload)) $payload = [];
      $result = abg_cn_request($method, $path, $payload);
      if (is_wp_error($result)) return $result;
      return rest_ensure_response($result);
    },
    'permission_callback' => '__return_true',
  ]);
});

/* =========================
   REST: /wp-json/alibeyg/v1/places
   (used by autocomplete)
   ========================= */
add_action('rest_api_init', function () {
  register_rest_route('alibeyg/v1', '/places', [
    'methods'  => 'GET',
    'callback' => function (WP_REST_Request $r) {
      $term   = sanitize_text_field($r->get_param('term') ?? '');
      $limit  = (int)($r->get_param('limit') ?? 7);
      $locale = preg_replace('/[^a-z]/i', '', $r->get_param('locale') ?? 'en');

      if (strlen($term) < 2) {
        return rest_ensure_response(['airports' => []]);
      }

      // Keep your existing upstream endpoint:
      $url = sprintf(
        'https://abengines.com/wp-content/plugins/adivaha//apps/modules/adivaha-fly-smart/apiflight_update_rates.php?action=getLocations&limit=%d&locale=%s&pid=%%7B%%7D&term=%s',
        $limit,
        rawurlencode($locale),
        rawurlencode($term)
      );

      $resp = wp_remote_get($url, ['timeout' => 15]);
      if (is_wp_error($resp)) return $resp;

      $code = wp_remote_retrieve_response_code($resp);
      $body = wp_remote_retrieve_body($resp);
      $data = json_decode($body, true);

      if ($code !== 200 || !is_array($data)) {
        return rest_ensure_response(['airports' => []]);
      }
      return rest_ensure_response($data);
    },
    'permission_callback' => '__return_true',
  ]);
});

/* =========================
   SHORTCODE to place the widget
   [alibeyg_travel_widget primary="#B8011F" primary_hover="#9a0119" flight_url="/flights-search/" hotel_url="/hotels-search/"]
   ========================= */
add_shortcode('alibeyg_travel_widget', function ($atts) {
  $atts = shortcode_atts([
    'primary'       => '#B8011F',
    'primary_hover' => '#9a0119',
    'flight_url'    => 'https://alibeyg.com.iq/flight/',
    'hotel_url'     => 'https://alibeyg.com.iq/hotel/',
    'visa_url'      => 'https://alibeyg.com.iq/visa/',
    'api_url'       => 'https://171.22.24.69/api/v1.0',
  ], $atts, 'alibeyg_travel_widget');

  ob_start();
  ?>
  <div id="adivaha-wrapper"
       data-primary="<?php echo esc_attr($atts['primary']); ?>"
       data-primary-hover="<?php echo esc_attr($atts['primary_hover']); ?>"
       data-flight-url="<?php echo esc_attr($atts['flight_url']); ?>"
       data-hotel-url="<?php echo esc_attr($atts['hotel_url']); ?>"
       data-visa-url="<?php echo esc_attr($atts['visa_url']); ?>"
       data-api-url="<?php echo esc_attr($atts['api_url']); ?>">
  </div>
  <?php
  return ob_get_clean();
});

/* =========================
   ASSETS (scoped CSS + JS + i18n/localized strings)
   ========================= */
add_action('wp_enqueue_scripts', function () {
  if (is_admin()) return;

  // Helper function to get translated string (Polylang or standard)
  $translate = function($string, $context = 'Alibeyg Travel Widget') {
    // Try Polylang first
    if (function_exists('pll__')) {
      return pll__($string);
    }
    // Fallback to standard translation
    return __($string, 'alibeyg-citynet');
  };

  // --- Translatable UI strings ---
  $tr = [
    'Flights'                  => $translate('Flights'),
    'Hotels'                   => $translate('Hotels'),
    'RoundTrip'                => $translate('Round Trip'),
    'OneWay'                   => $translate('One Way'),
    'MultiCity'                => $translate('Multi City'),
    'From'                     => $translate('From'),
    'To'                       => $translate('To'),
    'Depart'                   => $translate('Depart'),
    'Return'                   => $translate('Return'),
    'Passengers'               => $translate('Passengers'),
    'Class'                    => $translate('Class'),
    'Economy'                  => $translate('Economy'),
    'PremiumEconomy'           => $translate('Premium Economy'),
    'Business'                 => $translate('Business'),
    'FirstClass'               => $translate('First Class'),
    'SearchFlights'            => $translate('Search Flights'),
    'Destination'              => $translate('Destination'),
    'Checkin'                  => $translate('Check-in'),
    'Checkout'                 => $translate('Check-out'),
    'Rooms'                    => $translate('Rooms'),
    'Guests'                   => $translate('Guests'),
    'SearchHotels'             => $translate('Search Hotels'),
    'CityOrAirport'            => $translate('City or Airport'),
    'CityHotelPlace'           => $translate('City, Hotel, Place'),
    'PoweredBy'                => $translate('Powered by Travel Booking Engine'),
    'PleaseFillRequired'       => $translate('Please fill in all required fields'),
    'PleaseSelectReturn'       => $translate('Please select a return date'),
    'Adults'                   => $translate('Adults'),
    'Children'                 => $translate('Children'),
    'Infants'                  => $translate('Infants'),
    'TravelClass'              => $translate('Travel Class'),
    'Done'                     => $translate('Done'),
    'Passenger'                => $translate('Passenger'),
    'Passengers_lc'            => $translate('Passengers'),
    'Economy_lc'               => 'economy',
    'Business_lc'              => 'business',
    'PremiumEconomy_lc'        => 'premium-economy',
    'FirstClass_lc'            => 'first',
    'NoResults'                => $translate('No results'),
    'CIP'                      => $translate('CIP'),
    'TravelInsurance'          => $translate('Travel Insurance'),
    'Visa'                     => $translate('Visa'),
    'Destination_Country'      => $translate('Destination Country'),
    'TravelDuration'           => $translate('Travel Duration'),
    'SearchVisa'               => $translate('Search Visa'),
    'SearchCountry'            => $translate('Search country...'),
    'SelectCountry'            => $translate('Select destination country'),
    'Persons'                  => $translate('Persons'),
    'Person'                   => $translate('Person'),
    'Days'                     => $translate('Days'),
  ];

  // --- Scoped CSS (your styles + suggest & dropdown fixes) ---
  $css = <<<CSS
  #adivaha-wrapper { padding: 8px; position: relative; }
  #adivaha-wrapper .travel-widget-container,
  #adivaha-wrapper .travel-widget-container * { box-sizing: border-box; }

  /* (Your provided CSS – slightly scoped) */
  #adivaha-wrapper .travel-widget-container {
    font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,sans-serif;
    max-width: 1200px; margin: 0 auto; background:#fff; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,.1); overflow:visible;
  }
  #adivaha-wrapper .tw-tabs{ display:flex; border-bottom:1px solid #e5e7eb; background:#f9fafb; }
  #adivaha-wrapper .tw-tab{ flex:1; padding:16px 24px; border:none; background:transparent; font-size:16px; font-weight:600; color:#6b7280; cursor:pointer; transition:all .3s; display:flex; align-items:center; justify-content:center; gap:8px; border-bottom:4px solid transparent; }
  #adivaha-wrapper .tw-tab:hover{ background:#f3f4f6; }
  #adivaha-wrapper .tw-tab.active{ background:var(--primary-color,#B8011F); color:#fff; border-bottom-color:var(--primary-color,#B8011F); }
  #adivaha-wrapper .tw-tab-icon{ width:20px; height:20px; }
  #adivaha-wrapper .tw-content{ padding:24px; position:relative; overflow:visible; }
  #adivaha-wrapper .tw-tab-panel{ display:none; }
  #adivaha-wrapper .tw-tab-panel.active{ display:block; }
  #adivaha-wrapper .tw-trip-type{ display:flex; gap:24px; margin-bottom:24px; flex-wrap:wrap; }
  #adivaha-wrapper .tw-radio-label{ display:flex; align-items:center; gap:8px; cursor:pointer; font-size:14px; font-weight:500; }
  #adivaha-wrapper .tw-radio-label input[type=radio]{ width:16px; height:16px; accent-color:var(--primary-color,#B8011F); cursor:pointer; }
  #adivaha-wrapper .tw-form-row{ display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:16px; }
  @media (min-width:768px){
    #adivaha-wrapper .tw-form-row.horizontal{ grid-template-columns:repeat(6,1fr); }
    #adivaha-wrapper .tw-form-row.horizontal .tw-field-wide{ grid-column:span 2; }
    #adivaha-wrapper .tw-form-row.hotel-horizontal{ grid-template-columns:2fr 1fr 1fr 1fr 1fr; }
  }
  #adivaha-wrapper .tw-field{ position:relative; }
  #adivaha-wrapper .tw-label{ display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
  #adivaha-wrapper .tw-input-wrapper{ position:relative; }
  #adivaha-wrapper .tw-input-icon{ position:absolute; left:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#9ca3af; pointer-events:none; }
  #adivaha-wrapper .tw-input, #adivaha-wrapper .tw-select{ width:100%; padding:12px 12px 12px 40px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; transition:all .2s; background:#fff; }
  #adivaha-wrapper .tw-select{ appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; cursor:pointer; }
  #adivaha-wrapper .tw-input.no-icon, #adivaha-wrapper .tw-select.no-icon{ padding-left:12px; }
  #adivaha-wrapper .tw-input:focus, #adivaha-wrapper .tw-select:focus{ outline:none; border-color:var(--primary-color,#B8011F); box-shadow:0 0 0 3px rgba(184,1,31,.1); }
  #adivaha-wrapper .tw-input[readonly]{ cursor:pointer; background:#fff; }
  #adivaha-wrapper .tw-search-btn{ width:100%; padding:16px 24px; background:var(--primary-color,#B8011F); color:#fff; border:none; border-radius:8px; font-size:16px; font-weight:600; cursor:pointer; transition:all .3s; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 12px rgba(184,1,31,.3); }
  #adivaha-wrapper .tw-search-btn:hover{ background:var(--primary-hover,#9a0119); transform:translateY(-2px); box-shadow:0 6px 16px rgba(184,1,31,.4); }
  #adivaha-wrapper .tw-search-btn:active{ transform:translateY(0); }
  #adivaha-wrapper .tw-search-btn:disabled{ opacity:0.7; cursor:not-allowed; transform:none; }
  #adivaha-wrapper .animate-spin{ animation:spin 1s linear infinite; }
  @keyframes spin{ from{transform:rotate(0deg);} to{transform:rotate(360deg);} }
  #adivaha-wrapper .tw-footer{ background:#f9fafb; padding:12px 24px; text-align:center; font-size:12px; color:#6b7280; border-top:1px solid #e5e7eb; }
  #adivaha-wrapper .tw-icon{ display:inline-block; width:20px; height:20px; vertical-align:middle; }

  /* Passenger Dropdown - FIXED Z-INDEX */
  #adivaha-wrapper .tw-passenger-field{ position:relative; }
  #adivaha-wrapper .tw-passenger-dropdown{ 
    display:none; 
    position:absolute; 
    top:100%; 
    right:0; 
    margin-top:8px; 
    background:#fff; 
    border-radius:15px; 
    box-shadow:0 4px 20px rgba(39,38,44,.25); 
    width:320px; 
    z-index:9999; 
    padding:20px; 
  }
  #adivaha-wrapper .tw-passenger-dropdown.show{ display:block; }
  #adivaha-wrapper .tw-passenger-row{ display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #f0f0f0; }
  #adivaha-wrapper .tw-passenger-row:last-of-type{ border-bottom:none; }
  #adivaha-wrapper .tw-passenger-label{ font-size:14px; font-weight:600; color:#374151; margin:0; }
  #adivaha-wrapper .tw-passenger-controls{ display:flex; align-items:center; gap:15px; }
  #adivaha-wrapper .tw-passenger-btn{ width:32px; height:32px; border:1px solid #d1d5db; border-radius:6px; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; color:#6b7280; }
  #adivaha-wrapper .tw-passenger-btn:hover{ border-color:var(--primary-color,#B8011F); color:var(--primary-color,#B8011F); }
  #adivaha-wrapper .tw-passenger-btn:disabled{ opacity:.4; cursor:not-allowed; }
  #adivaha-wrapper .tw-passenger-count{ font-size:16px; font-weight:600; color:#111827; min-width:20px; text-align:center; }
  #adivaha-wrapper .tw-travel-class{ padding-top:20px; margin-top:10px; border-top:1px solid #f0f0f0; }
  #adivaha-wrapper .tw-radio-options{ margin-top:12px; }
  #adivaha-wrapper .tw-radio-option{ display:flex; align-items:center; gap:10px; padding:8px 0; cursor:pointer; font-size:14px; }
  #adivaha-wrapper .tw-radio-option input[type=radio]{ width:18px; height:18px; accent-color:var(--primary-color,#B8011F); cursor:pointer; }
  #adivaha-wrapper .tw-passenger-footer{ margin-top:20px; padding-top:15px; border-top:1px solid #f0f0f0; }
  #adivaha-wrapper .tw-done-btn{ width:100%; padding:12px; background:#000; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:all .2s; }
  #adivaha-wrapper .tw-done-btn:hover{ background:#333; }

  /* Additional Services Checkboxes */
  #adivaha-wrapper .tw-services{ 
    display:flex; 
    gap:24px; 
    margin-top:16px; 
    padding-top:16px; 
    border-top:1px solid #e5e7eb;
    flex-wrap:wrap;
  }
  #adivaha-wrapper .tw-checkbox-label{ 
    display:flex; 
    align-items:center; 
    gap:8px; 
    cursor:pointer; 
    font-size:13px; 
    font-weight:500;
    color:#374151;
  }
  #adivaha-wrapper .tw-checkbox-label input[type=checkbox]{ 
    width:18px; 
    height:18px; 
    accent-color:var(--primary-color,#B8011F); 
    cursor:pointer;
    border-radius:4px;
  }

  /* Country Selector Dropdown */
  #adivaha-wrapper .tw-country-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 10px 20px rgba(0,0,0,.12);
    max-height: 300px;
    overflow: hidden;
    z-index: 9998;
    flex-direction: column;
  }
  #adivaha-wrapper .tw-country-dropdown.show {
    display: flex;
  }
  #adivaha-wrapper .tw-country-search {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
  }
  #adivaha-wrapper .tw-country-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
  }
  #adivaha-wrapper .tw-country-search input:focus {
    outline: none;
    border-color: var(--primary-color, #B8011F);
  }
  #adivaha-wrapper .tw-country-list {
    overflow-y: auto;
    max-height: 240px;
  }
  #adivaha-wrapper .tw-country-item {
    padding: 10px 16px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s;
  }
  #adivaha-wrapper .tw-country-item:hover {
    background: #f3f4f6;
  }
  #adivaha-wrapper .tw-country-item.no-results {
    color: #9ca3af;
    cursor: default;
    text-align: center;
  }

  /* Person Dropdown (simplified, no class) */
  #adivaha-wrapper .tw-person-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(39,38,44,.25);
    width: 300px;
    z-index: 9999;
    padding: 20px;
  }
  #adivaha-wrapper .tw-person-dropdown.show {
    display: block;
  }

  /* Autocomplete dropdown - FIXED Z-INDEX */
  #adivaha-wrapper .tw-suggest{ position:relative; }
  #adivaha-wrapper .tw-suggest-list{
    position:absolute; left:0; right:0; top:calc(100% + 6px);
    background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 10px 20px rgba(0,0,0,.12);
    max-height:280px; overflow:auto; z-index:9998;
  }
  #adivaha-wrapper .tw-suggest-item{ padding:.55rem .65rem; cursor:pointer; display:flex; align-items:center; justify-content:space-between; }
  #adivaha-wrapper .tw-suggest-item:hover{ background:#f3f4f6; }
  #adivaha-wrapper .tw-code{ font-weight:700; opacity:.9; margin-inline-start:.6rem; }

  @media (max-width:767px){
    #adivaha-wrapper .tw-content{ padding:16px; }
    #adivaha-wrapper .tw-tab{ padding:12px 16px; font-size:14px; }
    #adivaha-wrapper .tw-form-row{ grid-template-columns:1fr; }
    #adivaha-wrapper .tw-passenger-dropdown{ width:280px; right:0; left:auto; }
  }
CSS;

  // --- Build JS (render widget + autocomplete + passengers + redirects) ---
  $js = <<<'JS'
  (function(){
    if (window.__abg_citynet_widget) return; window.__abg_citynet_widget = true;
    function ready(fn){ if(document.readyState!=='loading'){fn()} else {document.addEventListener('DOMContentLoaded', fn)} }

    ready(function(){
      var host = document.getElementById('adivaha-wrapper');
      if (!host) return;

      // Config from data-attrs
      var cfg = {
        primaryColor: host.getAttribute('data-primary') || '#B8011F',
        primaryHover: host.getAttribute('data-primary-hover') || '#9a0119',
        flightSearchUrl: host.getAttribute('data-flight-url') || '/flights-search/',
        hotelSearchUrl:  host.getAttribute('data-hotel-url')  || '/hotels-search/',
        visaSearchUrl:   host.getAttribute('data-visa-url')   || '/visa-search/'
      };

      // Strings (from wp_localize_script)
      var T = window.ABG_TR || {};

      // Apply colors
      host.style.setProperty('--primary-color', cfg.primaryColor);
      host.style.setProperty('--primary-hover', cfg.primaryHover);

      // Build widget HTML (using your structure, with i18n)
      host.innerHTML = [
        '<div class="travel-widget-container">',
          '<div class="tw-tabs">',
            '<button class="tw-tab active" data-tab="flights">',
              '<svg class="tw-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>',
              (T.Flights||'Flights'),
            '</button>',
            '<button class="tw-tab" data-tab="hotels">',
              '<svg class="tw-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
              (T.Hotels||'Hotels'),
            '</button>',
            '<button class="tw-tab" data-tab="visa">',
              '<svg class="tw-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
              (T.Visa||'Visa'),
            '</button>',
          '</div>',

          '<div class="tw-content">',

            // Flights
            '<div class="tw-tab-panel active" id="flights-panel">',
              '<div class="tw-trip-type">',
                '<label class="tw-radio-label"><input type="radio" name="tripType" value="round-trip" checked><span>'+(T.RoundTrip||'Round Trip')+'</span></label>',
                '<label class="tw-radio-label"><input type="radio" name="tripType" value="one-way"><span>'+(T.OneWay||'One Way')+'</span></label>',
                '<label class="tw-radio-label"><input type="radio" name="tripType" value="multi-city"><span>'+(T.MultiCity||'Multi City')+'</span></label>',
              '</div>',

              '<div class="tw-form-row horizontal">',

                // From (with autocomplete wrapper)
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.From||'From')+'</label>',
                  '<div class="tw-input-wrapper tw-suggest">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                    '<input type="text" class="tw-input" id="flightFrom" placeholder="'+(T.CityOrAirport||'City or Airport')+'">',
                    '<div class="tw-suggest-list" id="fromSuggest" style="display:none;"></div>',
                  '</div>',
                '</div>',

                // To (with autocomplete wrapper)
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.To||'To')+'</label>',
                  '<div class="tw-input-wrapper tw-suggest">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                    '<input type="text" class="tw-input" id="flightTo" placeholder="'+(T.CityOrAirport||'City or Airport')+'">',
                    '<div class="tw-suggest-list" id="toSuggest" style="display:none;"></div>',
                  '</div>',
                '</div>',

                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.Depart||'Depart')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    '<input type="date" class="tw-input" id="departDate">',
                  '</div>',
                '</div>',

                '<div class="tw-field" id="returnDateField">',
                  '<label class="tw-label">'+(T.Return||'Return')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    '<input type="date" class="tw-input" id="returnDate">',
                  '</div>',
                '</div>',

                // Passengers (custom dropdown)
                '<div class="tw-field tw-passenger-field">',
                  '<label class="tw-label">'+(T.Passengers||'Passengers')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                    '<input type="text" readonly class="tw-input" id="passengerDisplay" style="cursor:pointer;" value="1 '+(T.Passenger||'Passenger')+', '+(T.Economy||'Economy')+'">',
                  '</div>',
                  // Dropdown
                  '<div class="tw-passenger-dropdown" id="passengerDropdown">',
                    '<div class="tw-passenger-row"><p class="tw-passenger-label">'+(T.Adults||'Adults')+'</p><div class="tw-passenger-controls">',
                      '<button type="button" class="tw-passenger-btn" id="adultsDec">–</button>',
                      '<span class="tw-passenger-count" id="adultsCount">1</span>',
                      '<button type="button" class="tw-passenger-btn" id="adultsInc">+</button>',
                    '</div></div>',
                    '<div class="tw-passenger-row"><p class="tw-passenger-label">'+(T.Children||'Children')+'</p><div class="tw-passenger-controls">',
                      '<button type="button" class="tw-passenger-btn" id="childrenDec">–</button>',
                      '<span class="tw-passenger-count" id="childrenCount">0</span>',
                      '<button type="button" class="tw-passenger-btn" id="childrenInc">+</button>',
                    '</div></div>',
                    '<div class="tw-passenger-row"><p class="tw-passenger-label">'+(T.Infants||'Infants')+'</p><div class="tw-passenger-controls">',
                      '<button type="button" class="tw-passenger-btn" id="infantsDec">–</button>',
                      '<span class="tw-passenger-count" id="infantsCount">0</span>',
                      '<button type="button" class="tw-passenger-btn" id="infantsInc">+</button>',
                    '</div></div>',
                    '<div class="tw-travel-class">',
                      '<div class="tw-label">'+(T.TravelClass||'Travel Class')+'</div>',
                      '<div class="tw-radio-options">',
                        '<label class="tw-radio-option"><input type="radio" name="flightClassDropdown" value="'+(T.Economy_lc||'economy')+'" checked> '+(T.Economy||'Economy')+'</label>',
                        '<label class="tw-radio-option"><input type="radio" name="flightClassDropdown" value="'+(T.PremiumEconomy_lc||'premium-economy')+'"> '+(T.PremiumEconomy||'Premium Economy')+'</label>',
                        '<label class="tw-radio-option"><input type="radio" name="flightClassDropdown" value="'+(T.Business_lc||'business')+'"> '+(T.Business||'Business')+'</label>',
                        '<label class="tw-radio-option"><input type="radio" name="flightClassDropdown" value="'+(T.FirstClass_lc||'first')+'"> '+(T.FirstClass||'First Class')+'</label>',
                      '</div>',
                    '</div>',
                    '<div class="tw-passenger-footer"><button type="button" class="tw-done-btn" id="passengersDone">'+(T.Done||'Done')+'</button></div>',
                  '</div>',
                  // hidden mirrors for querystring
                  '<input type="hidden" id="hidAdults" value="1"/>',
                  '<input type="hidden" id="hidChildren" value="0"/>',
                  '<input type="hidden" id="hidInfants" value="0"/>',
                  '<input type="hidden" id="flightClass" value="'+(T.Economy_lc||'economy')+'"/>',
                '</div>',

                // Class (showing selection but controlled by dropdown) - REMOVED DISABLED
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.Class||'Class')+'</label>',
                  '<select class="tw-select no-icon" id="flightClassMirror">',
                    '<option value="'+(T.Economy_lc||'economy')+'" selected>'+(T.Economy||'Economy')+'</option>',
                    '<option value="'+(T.PremiumEconomy_lc||'premium-economy')+'">'+(T.PremiumEconomy||'Premium Economy')+'</option>',
                    '<option value="'+(T.Business_lc||'business')+'">'+(T.Business||'Business')+'</option>',
                    '<option value="'+(T.FirstClass_lc||'first')+'">'+(T.FirstClass||'First Class')+'</option>',
                  '</select>',
                '</div>',

              '</div>',

              '<button class="tw-search-btn" id="btnSearchFlights">',
                '<svg class="tw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                (T.SearchFlights||'Search Flights'),
              '</button>',
              
              // Additional Services
              '<div class="tw-services">',
                '<label class="tw-checkbox-label">',
                  '<input type="checkbox" id="cipService" value="cip">',
                  '<span>'+(T.CIP||'CIP')+'</span>',
                '</label>',
                '<label class="tw-checkbox-label">',
                  '<input type="checkbox" id="insuranceService" value="insurance">',
                  '<span>'+(T.TravelInsurance||'Travel Insurance')+'</span>',
                '</label>',
              '</div>',
            '</div>',

            // Hotels
            '<div class="tw-tab-panel" id="hotels-panel">',
              '<div class="tw-form-row hotel-horizontal">',
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.Destination||'Destination')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                    '<input type="text" class="tw-input" id="hotelDestination" placeholder="'+(T.CityHotelPlace||'City, Hotel, Place')+'">',
                  '</div>',
                '</div>',
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.Checkin||'Check-in')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    '<input type="date" class="tw-input" id="checkIn">',
                  '</div>',
                '</div>',
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.Checkout||'Check-out')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    '<input type="date" class="tw-input" id="checkOut">',
                  '</div>',
                '</div>',
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.Rooms||'Rooms')+'</label>',
                  '<select class="tw-select no-icon" id="rooms">',
                    '<option value="1">1</option>',
                    '<option value="2">2</option>',
                    '<option value="3">3</option>',
                    '<option value="4">4</option>',
                    '<option value="5">5</option>',
                    '<option value="6">6</option>',
                  '</select>',
                '</div>',
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.Guests||'Guests')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                    '<select class="tw-select" id="guests">',
                      '<option value="1">1</option>',
                      '<option value="2">2</option>',
                      '<option value="3">3</option>',
                      '<option value="4">4</option>',
                      '<option value="5">5</option>',
                      '<option value="6">6</option>',
                      '<option value="7">7</option>',
                      '<option value="8">8</option>',
                    '</select>',
                  '</div>',
                '</div>',
              '</div>',
              '<button class="tw-search-btn" id="btnSearchHotels">',
                '<svg class="tw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                (T.SearchHotels||'Search Hotels'),
              '</button>',
            '</div>',

            // Visa
            '<div class="tw-tab-panel" id="visa-panel">',
              '<div class="tw-form-row">',
                
                // Country Selector with Search
                '<div class="tw-field" style="position:relative;">',
                  '<label class="tw-label">'+(T.Destination_Country||'Destination')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>',
                    '<input type="text" readonly class="tw-input" id="visaCountryDisplay" style="cursor:pointer;" placeholder="'+(T.SelectCountry||'Select destination country')+'">',
                    '<input type="hidden" id="visaCountryCode" value="">',
                  '</div>',
                  // Country Dropdown
                  '<div class="tw-country-dropdown" id="countryDropdown">',
                    '<div class="tw-country-search">',
                      '<input type="text" id="countrySearchInput" placeholder="'+(T.SearchCountry||'Search country...')+'">',
                    '</div>',
                    '<div class="tw-country-list" id="countryList"></div>',
                  '</div>',
                '</div>',

                // Travel Duration
                '<div class="tw-field">',
                  '<label class="tw-label">'+(T.TravelDuration||'Travel Duration')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    '<select class="tw-select" id="visaDuration">',
                      '<option value="7">7 '+(T.Days||'Days')+'</option>',
                      '<option value="14">14 '+(T.Days||'Days')+'</option>',
                      '<option value="30" selected>30 '+(T.Days||'Days')+'</option>',
                      '<option value="60">60 '+(T.Days||'Days')+'</option>',
                      '<option value="90">90 '+(T.Days||'Days')+'</option>',
                    '</select>',
                  '</div>',
                '</div>',

                // Persons Selector
                '<div class="tw-field" style="position:relative;">',
                  '<label class="tw-label">'+(T.Persons||'Persons')+'</label>',
                  '<div class="tw-input-wrapper">',
                    '<svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                    '<input type="text" readonly class="tw-input" id="visaPersonDisplay" style="cursor:pointer;" value="1 '+(T.Person||'Person')+'">',
                  '</div>',
                  // Person Dropdown (simplified)
                  '<div class="tw-person-dropdown" id="personDropdown">',
                    '<div class="tw-passenger-row"><p class="tw-passenger-label">'+(T.Adults||'Adults')+'</p><div class="tw-passenger-controls">',
                      '<button type="button" class="tw-passenger-btn" id="visaAdultsDec">–</button>',
                      '<span class="tw-passenger-count" id="visaAdultsCount">1</span>',
                      '<button type="button" class="tw-passenger-btn" id="visaAdultsInc">+</button>',
                    '</div></div>',
                    '<div class="tw-passenger-row"><p class="tw-passenger-label">'+(T.Children||'Children')+'</p><div class="tw-passenger-controls">',
                      '<button type="button" class="tw-passenger-btn" id="visaChildrenDec">–</button>',
                      '<span class="tw-passenger-count" id="visaChildrenCount">0</span>',
                      '<button type="button" class="tw-passenger-btn" id="visaChildrenInc">+</button>',
                    '</div></div>',
                    '<div class="tw-passenger-footer"><button type="button" class="tw-done-btn" id="visaPersonDone">'+(T.Done||'Done')+'</button></div>',
                  '</div>',
                  '<input type="hidden" id="hidVisaAdults" value="1"/>',
                  '<input type="hidden" id="hidVisaChildren" value="0"/>',
                '</div>',

              '</div>',

              '<button class="tw-search-btn" id="btnSearchVisa">',
                '<svg class="tw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                (T.SearchVisa||'Search Visa'),
              '</button>',
            '</div>',

          '</div>',
          '<div class="tw-footer">'+(T.PoweredBy||'Powered by Travel Booking Engine')+'</div>',
        '</div>'
      ].join('');

      var root = host.querySelector('.travel-widget-container');

      // Tabs
      root.querySelectorAll('.tw-tab').forEach(function(btn){
        btn.addEventListener('click', function(){
          root.querySelectorAll('.tw-tab').forEach(function(b){ b.classList.remove('active'); });
          btn.classList.add('active');
          var tab = btn.getAttribute('data-tab');
          root.querySelectorAll('.tw-tab-panel').forEach(function(p){ p.classList.remove('active'); });
          root.querySelector('#'+tab+'-panel').classList.add('active');
        });
      });

      // Trip type toggle
      root.querySelectorAll('input[name="tripType"]').forEach(function(r){
        r.addEventListener('change', function(){
          var ret = root.querySelector('#returnDateField');
          ret.style.display = (r.value === 'one-way') ? 'none' : 'block';
        });
      });

      // Dates min=today
      var today = new Date().toISOString().split('T')[0];
      ['departDate','returnDate','checkIn','checkOut'].forEach(function(id){
        var el = root.querySelector('#'+id);
        if (el) el.setAttribute('min', today);
      });

      // ===== Passengers dropdown =====
      var pax = { adults:1, children:0, infants:0 };
      var disp = root.querySelector('#passengerDisplay');
      var dd   = root.querySelector('#passengerDropdown');
      var hA   = root.querySelector('#hidAdults');
      var hC   = root.querySelector('#hidChildren');
      var hI   = root.querySelector('#hidInfants');
      var hCls = root.querySelector('#flightClass');
      var clsMirror = root.querySelector('#flightClassMirror');

      function updatePassengerDisplay(){
        var total = pax.adults + pax.children + pax.infants;
        var text = total + ' ' + (total===1 ? (T.Passenger||'Passenger') : (T.Passengers_lc||'Passengers'));
        var clsLabel = clsMirror.options[clsMirror.selectedIndex].text || (T.Economy||'Economy');
        disp.value = text + ', ' + clsLabel;
        hA.value = pax.adults; hC.value = pax.children; hI.value = pax.infants;
        root.querySelector('#adultsCount').textContent = pax.adults;
        root.querySelector('#childrenCount').textContent = pax.children;
        root.querySelector('#infantsCount').textContent = pax.infants;
      }
      function clamp(){
        pax.adults   = Math.max(1, Math.min(9, pax.adults));
        pax.children = Math.max(0, Math.min(9, pax.children));
        pax.infants  = Math.max(0, Math.min(pax.adults, pax.infants)); // infants <= adults
      }
      
      // Toggle dropdown
      disp.addEventListener('click', function(e){ 
        e.stopPropagation(); 
        dd.classList.toggle('show'); 
      });
      
      // Close on outside click
      document.addEventListener('click', function(e){ 
        if (!dd.contains(e.target) && e.target!==disp) {
          dd.classList.remove('show'); 
        }
      });
      
      // Prevent closing when clicking inside
      dd.addEventListener('click', function(e){ e.stopPropagation(); });

      // Counter buttons
      root.querySelector('#adultsInc').addEventListener('click', function(){ pax.adults++; clamp(); updatePassengerDisplay(); });
      root.querySelector('#adultsDec').addEventListener('click', function(){ pax.adults--; clamp(); updatePassengerDisplay(); });
      root.querySelector('#childrenInc').addEventListener('click', function(){ pax.children++; clamp(); updatePassengerDisplay(); });
      root.querySelector('#childrenDec').addEventListener('click', function(){ pax.children--; clamp(); updatePassengerDisplay(); });
      root.querySelector('#infantsInc').addEventListener('click', function(){ pax.infants++; clamp(); updatePassengerDisplay(); });
      root.querySelector('#infantsDec').addEventListener('click', function(){ pax.infants--; clamp(); updatePassengerDisplay(); });

      // Class radio buttons in dropdown
      dd.querySelectorAll('input[name="flightClassDropdown"]').forEach(function(r){
        r.addEventListener('change', function(){
          hCls.value = r.value;
          // Sync mirror select
          for (var i=0;i<clsMirror.options.length;i++){
            clsMirror.options[i].selected = (clsMirror.options[i].value === r.value);
          }
          updatePassengerDisplay();
        });
      });
      
      // Sync when class mirror changes directly
      clsMirror.addEventListener('change', function(){
        hCls.value = clsMirror.value;
        // Update radio in dropdown
        dd.querySelectorAll('input[name="flightClassDropdown"]').forEach(function(r){
          r.checked = (r.value === clsMirror.value);
        });
        updatePassengerDisplay();
      });
      
      // Done button
      root.querySelector('#passengersDone').addEventListener('click', function(){ 
        dd.classList.remove('show'); 
      });

      clamp(); 
      updatePassengerDisplay();

      // ===== Visa: Country selector with search =====
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

      function renderCountries(filter){
        var filtered = filter ? countries.filter(function(c){
          return c.name.toLowerCase().indexOf(filter.toLowerCase()) > -1;
        }) : countries;
        
        if (!filtered.length) {
          countryList.innerHTML = '<div class="tw-country-item no-results">'+(T.NoResults||'No results')+'</div>';
          return;
        }
        
        countryList.innerHTML = filtered.map(function(c){
          return '<div class="tw-country-item" data-code="'+c.code+'" data-name="'+c.name+'">'+c.name+'</div>';
        }).join('');
        
        countryList.querySelectorAll('.tw-country-item:not(.no-results)').forEach(function(item){
          item.addEventListener('click', function(){
            countryDisp.value = item.getAttribute('data-name');
            countryCode.value = item.getAttribute('data-code');
            countryDD.classList.remove('show');
            countrySearch.value = '';
          });
        });
      }

      countryDisp.addEventListener('click', function(e){
        e.stopPropagation();
        countryDD.classList.toggle('show');
        if (countryDD.classList.contains('show')) {
          renderCountries('');
          countrySearch.focus();
        }
      });

      countrySearch.addEventListener('input', function(){
        renderCountries(countrySearch.value);
      });

      document.addEventListener('click', function(e){
        if (!countryDD.contains(e.target) && e.target !== countryDisp) {
          countryDD.classList.remove('show');
        }
      });

      countryDD.addEventListener('click', function(e){ e.stopPropagation(); });

      // ===== Visa: Person dropdown (simplified) =====
      var visaPax = { adults:1, children:0 };
      var visaPDisp = root.querySelector('#visaPersonDisplay');
      var visaPDD = root.querySelector('#personDropdown');
      var visaHAdults = root.querySelector('#hidVisaAdults');
      var visaHChildren = root.querySelector('#hidVisaChildren');

      function updateVisaPersonDisplay(){
        var total = visaPax.adults + visaPax.children;
        var text = total + ' ' + (total===1 ? (T.Person||'Person') : (T.Persons||'Persons'));
        visaPDisp.value = text;
        visaHAdults.value = visaPax.adults;
        visaHChildren.value = visaPax.children;
        root.querySelector('#visaAdultsCount').textContent = visaPax.adults;
        root.querySelector('#visaChildrenCount').textContent = visaPax.children;
      }

      function clampVisa(){
        visaPax.adults = Math.max(1, Math.min(9, visaPax.adults));
        visaPax.children = Math.max(0, Math.min(9, visaPax.children));
      }

      visaPDisp.addEventListener('click', function(e){ 
        e.stopPropagation(); 
        visaPDD.classList.toggle('show'); 
      });

      document.addEventListener('click', function(e){ 
        if (!visaPDD.contains(e.target) && e.target !== visaPDisp) {
          visaPDD.classList.remove('show'); 
        }
      });

      visaPDD.addEventListener('click', function(e){ e.stopPropagation(); });

      root.querySelector('#visaAdultsInc').addEventListener('click', function(){ visaPax.adults++; clampVisa(); updateVisaPersonDisplay(); });
      root.querySelector('#visaAdultsDec').addEventListener('click', function(){ visaPax.adults--; clampVisa(); updateVisaPersonDisplay(); });
      root.querySelector('#visaChildrenInc').addEventListener('click', function(){ visaPax.children++; clampVisa(); updateVisaPersonDisplay(); });
      root.querySelector('#visaChildrenDec').addEventListener('click', function(){ visaPax.children--; clampVisa(); updateVisaPersonDisplay(); });
      root.querySelector('#visaPersonDone').addEventListener('click', function(){ visaPDD.classList.remove('show'); });

      clampVisa();
      updateVisaPersonDisplay();

      // ===== Autocomplete for From/To =====
      function fetchAirports(term){
        if (!term || term.trim().length < 2) return Promise.resolve([]);
        var url = '/wp-json/alibeyg/v1/places?term=' + encodeURIComponent(term.trim()) + '&limit=7&locale=' + (document.documentElement.lang || 'en');
        return fetch(url).then(function(r){ if(!r.ok) return []; return r.json(); })
                         .then(function(j){ return (j && j.airports) ? j.airports : []; })
                         .catch(function(){ return []; });
      }
      
      function mountSuggest(inputId, listId){
        var input = root.querySelector('#'+inputId);
        var list  = root.querySelector('#'+listId);
        var timer = null;
        
        function hide(){ list.style.display='none'; list.innerHTML=''; }
        
        function show(items){
          if (!items.length){ hide(); return; }
          list.innerHTML = items.map(function(a){
            var label = (a.city_fullname||a.fullname||'') + ' — <span class="tw-code">'+(a.code||'')+'</span>';
            return '<div class="tw-suggest-item" data-code="'+(a.code||'')+'" data-label="'+(a.fullname||'')+'">'+label+'</div>';
          }).join('');
          list.style.display = 'block';
          list.querySelectorAll('.tw-suggest-item').forEach(function(it){
            it.addEventListener('click', function(e){
              e.preventDefault();
              input.value = it.getAttribute('data-code') || '';
              hide();
            });
          });
        }
        
        input.addEventListener('input', function(){
          clearTimeout(timer);
          var q = input.value;
          if (!q || q.length < 2) { hide(); return; }
          timer = setTimeout(function(){
            fetchAirports(q).then(show).catch(hide);
          }, 180);
        });
        
        input.addEventListener('blur', function(){ setTimeout(hide, 150); });
        
        input.addEventListener('focus', function(){
          if (input.value && input.value.length>=2) {
            fetchAirports(input.value).then(show).catch(hide);
          }
        });
      }
      
      mountSuggest('flightFrom','fromSuggest');
      mountSuggest('flightTo','toSuggest');

      // ===== Search actions =====
      function doSearchFlights(){
        var from   = (root.querySelector('#flightFrom').value||'').trim();
        var to     = (root.querySelector('#flightTo').value||'').trim();
        var depart = root.querySelector('#departDate').value;
        var ret    = root.querySelector('#returnDate').value;
        var trip   = root.querySelector('input[name="tripType"]:checked').value;
        var fclass = hCls.value;
        
        // Get service checkboxes
        var cipChecked = root.querySelector('#cipService').checked;
        var insuranceChecked = root.querySelector('#insuranceService').checked;

        if (!from || !to || !depart) { alert(T.PleaseFillRequired||'Please fill in all required fields'); return; }
        if (trip === 'round-trip' && !ret) { alert(T.PleaseSelectReturn||'Please select a return date'); return; }

        // Map class names to API format
        var cabinMap = {
          'economy': 'Economy',
          'premium-economy': 'PremiumEconomy',
          'business': 'Business',
          'first': 'First'
        };

        // Build passenger array
        var passengers = [];
        if (pax.adults > 0) passengers.push({"Code": "ADT", "Quantity": pax.adults});
        if (pax.children > 0) passengers.push({"Code": "CHD", "Quantity": pax.children});
        if (pax.infants > 0) passengers.push({"Code": "INF", "Quantity": pax.infants});

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
          "Lang": (document.documentElement.lang || "EN").toUpperCase(),
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
        } catch(e) {
          console.error('SessionStorage error:', e);
        }

        // Build URL with all parameters (base64 encoded payload)
        var params = [];
        params.push('from='+encodeURIComponent(from));
        params.push('to='+encodeURIComponent(to));
        params.push('depart='+encodeURIComponent(depart));
        if (ret) params.push('return='+encodeURIComponent(ret));
        params.push('adults='+encodeURIComponent(pax.adults));
        params.push('children='+encodeURIComponent(pax.children));
        params.push('infants='+encodeURIComponent(pax.infants));
        params.push('cabin='+encodeURIComponent(cabinMap[fclass] || "Economy"));
        params.push('type='+encodeURIComponent(trip === 'round-trip' ? 'twoWay' : 'oneWay'));
        if (cipChecked) params.push('cip=1');
        if (insuranceChecked) params.push('insurance=1');
        
        // Add encoded payload
        params.push('searchPayload='+encodeURIComponent(btoa(JSON.stringify(payload))));
        
        console.log('Redirecting to:', cfg.flightSearchUrl + '?' + params.join('&'));
        console.log('Payload stored in sessionStorage');
        
        // Redirect
        window.location.href = cfg.flightSearchUrl + '?' + params.join('&');
      }
      
      function doSearchHotels(){
        var dest = (root.querySelector('#hotelDestination').value||'').trim();
        var ci   = root.querySelector('#checkIn').value;
        var co   = root.querySelector('#checkOut').value;
        var rooms= root.querySelector('#rooms').value;
        var guests=root.querySelector('#guests').value;

        if (!dest || !ci || !co) { alert(T.PleaseFillRequired||'Please fill in all required fields'); return; }

        var payload = {
          "Lang": (document.documentElement.lang || "EN").toUpperCase(),
          "destination": dest,
          "checkIn": ci,
          "checkOut": co,
          "rooms": parseInt(rooms),
          "guests": parseInt(guests)
        };

        // Create form and submit
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = cfg.hotelSearchUrl;
        form.style.display = 'none';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'searchData';
        input.value = JSON.stringify(payload);
        form.appendChild(input);

        // Add individual fields
        var fields = {
          'destination': dest,
          'checkin': ci,
          'checkout': co,
          'rooms': rooms,
          'guests': guests
        };

        for (var key in fields) {
          var inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = key;
          inp.value = fields[key];
          form.appendChild(inp);
        }

        document.body.appendChild(form);
        form.submit();
      }

      function doSearchVisa(){
        var country = countryCode.value;
        var countryName = countryDisp.value;
        var duration = root.querySelector('#visaDuration').value;

        if (!country || !countryName) { 
          alert(T.PleaseFillRequired||'Please fill in all required fields'); 
          return; 
        }

        var payload = {
          "Lang": (document.documentElement.lang || "EN").toUpperCase(),
          "country": country,
          "countryName": countryName,
          "duration": parseInt(duration),
          "adults": visaPax.adults,
          "children": visaPax.children
        };

        // Create form and submit
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = cfg.visaSearchUrl;
        form.style.display = 'none';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'searchData';
        input.value = JSON.stringify(payload);
        form.appendChild(input);

        // Add individual fields
        var fields = {
          'country': country,
          'countryName': countryName,
          'duration': duration,
          'adults': visaPax.adults,
          'children': visaPax.children
        };

        for (var key in fields) {
          var inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = key;
          inp.value = fields[key];
          form.appendChild(inp);
        }

        document.body.appendChild(form);
        form.submit();
      }
      
      function doSearchHotels(){
        var dest = (root.querySelector('#hotelDestination').value||'').trim();
        var ci   = root.querySelector('#checkIn').value;
        var co   = root.querySelector('#checkOut').value;
        var rooms= root.querySelector('#rooms').value;
        var guests=root.querySelector('#guests').value;

        if (!dest || !ci || !co) { alert(T.PleaseFillRequired||'Please fill in all required fields'); return; }

        var searchBtn = root.querySelector('#btnSearchHotels');
        var originalText = searchBtn.innerHTML;
        searchBtn.innerHTML = '<svg class="tw-icon animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> '+(T.SearchHotels||'Search Hotels')+'...';
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
        .then(function(response){ return response.json(); })
        .then(function(result){
          searchBtn.innerHTML = originalText;
          searchBtn.disabled = false;
          
          if (result.status === 200 && result.data) {
            var params = [];
            params.push('destination='+encodeURIComponent(dest));
            params.push('checkin='+encodeURIComponent(ci));
            params.push('checkout='+encodeURIComponent(co));
            params.push('rooms='+encodeURIComponent(rooms));
            params.push('guests='+encodeURIComponent(guests));
            
            try {
              sessionStorage.setItem('hotelSearchResults', JSON.stringify(result.data));
            } catch(e) {}
            
            window.location.href = cfg.hotelSearchUrl + '?' + params.join('&');
          } else {
            alert('Search failed. Please try again.');
          }
        })
        .catch(function(error){
          searchBtn.innerHTML = originalText;
          searchBtn.disabled = false;
          console.error('Search error:', error);
          alert('Search failed. Please try again.');
        });
      }

      function doSearchVisa(){
        var country = countryCode.value;
        var countryName = countryDisp.value;
        var duration = root.querySelector('#visaDuration').value;

        if (!country || !countryName) { 
          alert(T.PleaseFillRequired||'Please fill in all required fields'); 
          return; 
        }

        var searchBtn = root.querySelector('#btnSearchVisa');
        var originalText = searchBtn.innerHTML;
        searchBtn.innerHTML = '<svg class="tw-icon animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> '+(T.SearchVisa||'Search Visa')+'...';
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
        .then(function(response){ return response.json(); })
        .then(function(result){
          searchBtn.innerHTML = originalText;
          searchBtn.disabled = false;
          
          if (result.status === 200 && result.data) {
            var params = [];
            params.push('country='+encodeURIComponent(country));
            params.push('countryName='+encodeURIComponent(countryName));
            params.push('duration='+encodeURIComponent(duration));
            params.push('adults='+encodeURIComponent(visaPax.adults));
            params.push('children='+encodeURIComponent(visaPax.children));
            
            try {
              sessionStorage.setItem('visaSearchResults', JSON.stringify(result.data));
            } catch(e) {}
            
            window.location.href = cfg.visaSearchUrl + '?' + params.join('&');
          } else {
            alert('Search failed. Please try again.');
          }
        })
        .catch(function(error){
          searchBtn.innerHTML = originalText;
          searchBtn.disabled = false;
          console.error('Search error:', error);
          alert('Search failed. Please try again.');
        });
      }

      root.querySelector('#btnSearchFlights').addEventListener('click', function(e){ e.preventDefault(); doSearchFlights(); });
      root.querySelector('#btnSearchHotels').addEventListener('click', function(e){ e.preventDefault(); doSearchHotels(); });
      root.querySelector('#btnSearchVisa').addEventListener('click', function(e){ e.preventDefault(); doSearchVisa(); });

      // Provide globals too (in case you keep inline onclick elsewhere)
      window.searchFlights = doSearchFlights;
      window.searchHotels  = doSearchHotels;
      window.searchVisa = doSearchVisa;
    });
  })();
JS;

  // Register and enqueue
  wp_register_style('abg-citynet-inline-style', false);
  wp_enqueue_style('abg-citynet-inline-style');
  wp_add_inline_style('abg-citynet-inline-style', $css);

  wp_register_script('abg-citynet-inline-script', '', [], null, true);
  // localize strings BEFORE adding inline script
  wp_localize_script('abg-citynet-inline-script', 'ABG_TR', $tr);
  wp_enqueue_script('abg-citynet-inline-script');
  wp_add_inline_script('abg-citynet-inline-script', $js);
}, 100001);