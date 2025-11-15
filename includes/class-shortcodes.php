<?php
/**
 * Shortcode Handler Class
 *
 * Handles all shortcodes for the plugin
 *
 * @package Alibeyg_Citynet_Bridge
 * @since 0.5.0
 */

defined('ABSPATH') || exit;

/**
 * Shortcodes Class
 */
class ABG_Citynet_Shortcodes {

    /**
     * Plugin directory path
     *
     * @var string
     */
    private $plugin_dir;

    /**
     * Plugin URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Constructor
     *
     * @param string $plugin_dir Plugin directory path
     * @param string $plugin_url Plugin URL
     */
    public function __construct($plugin_dir, $plugin_url) {
        $this->plugin_dir = $plugin_dir;
        $this->plugin_url = $plugin_url;
    }

    /**
     * Register shortcodes
     */
    public function register() {
        add_shortcode('alibeyg_travel_widget', array($this, 'render_travel_widget'));
    }

    /**
     * Render travel widget shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Widget HTML
     */
    public function render_travel_widget($atts) {
        $atts = shortcode_atts(array(
            'primary'       => '#B8011F',
            'primary_hover' => '#9a0119',
            'flight_url'    => 'https://alibeyg.com.iq/flight/',
            'hotel_url'     => 'https://alibeyg.com.iq/hotel/',
            'visa_url'      => 'https://alibeyg.com.iq/visa/',
            'api_url'       => 'https://171.22.24.69/api/v1.0',
        ), $atts, 'alibeyg_travel_widget');

        ob_start();
        ?>
        <div id="adivaha-wrapper"
             data-primary="<?php echo esc_attr($atts['primary']); ?>"
             data-primary-hover="<?php echo esc_attr($atts['primary_hover']); ?>"
             data-flight-url="<?php echo esc_attr($atts['flight_url']); ?>"
             data-hotel-url="<?php echo esc_attr($atts['hotel_url']); ?>"
             data-visa-url="<?php echo esc_attr($atts['visa_url']); ?>"
             data-api-url="<?php echo esc_attr($atts['api_url']); ?>">
            <?php include $this->plugin_dir . '/templates/widget-template.php'; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (is_admin()) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'abg-citynet-widget',
            $this->plugin_url . '/assets/css/travel-widget.css',
            array(),
            '0.5.0'
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'abg-citynet-widget',
            $this->plugin_url . '/assets/js/travel-widget.js',
            array(),
            '0.5.0',
            true
        );

        // Localize strings for JavaScript
        wp_localize_script('abg-citynet-widget', 'ABG_TR', $this->get_translations());
    }

    /**
     * Get translated strings
     *
     * @return array Translated strings
     */
    private function get_translations() {
        $translate = function($string) {
            if (function_exists('pll__')) {
                return pll__($string);
            }
            return __($string, 'alibeyg-citynet');
        };

        return array(
            'Flights'              => $translate('Flights'),
            'Hotels'               => $translate('Hotels'),
            'RoundTrip'            => $translate('Round Trip'),
            'OneWay'               => $translate('One Way'),
            'MultiCity'            => $translate('Multi City'),
            'From'                 => $translate('From'),
            'To'                   => $translate('To'),
            'Depart'               => $translate('Depart'),
            'Return'               => $translate('Return'),
            'Passengers'           => $translate('Passengers'),
            'Class'                => $translate('Class'),
            'Economy'              => $translate('Economy'),
            'PremiumEconomy'       => $translate('Premium Economy'),
            'Business'             => $translate('Business'),
            'FirstClass'           => $translate('First Class'),
            'SearchFlights'        => $translate('Search Flights'),
            'Destination'          => $translate('Destination'),
            'Checkin'              => $translate('Check-in'),
            'Checkout'             => $translate('Check-out'),
            'Rooms'                => $translate('Rooms'),
            'Guests'               => $translate('Guests'),
            'SearchHotels'         => $translate('Search Hotels'),
            'CityOrAirport'        => $translate('City or Airport'),
            'CityHotelPlace'       => $translate('City, Hotel, Place'),
            'PoweredBy'            => $translate('Powered by Travel Booking Engine'),
            'PleaseFillRequired'   => $translate('Please fill in all required fields'),
            'PleaseSelectReturn'   => $translate('Please select a return date'),
            'Adults'               => $translate('Adults'),
            'Children'             => $translate('Children'),
            'Infants'              => $translate('Infants'),
            'TravelClass'          => $translate('Travel Class'),
            'Done'                 => $translate('Done'),
            'Passenger'            => $translate('Passenger'),
            'Passengers_lc'        => $translate('Passengers'),
            'Economy_lc'           => 'economy',
            'Business_lc'          => 'business',
            'PremiumEconomy_lc'    => 'premium-economy',
            'FirstClass_lc'        => 'first',
            'NoResults'            => $translate('No results'),
            'CIP'                  => $translate('CIP'),
            'TravelInsurance'      => $translate('Travel Insurance'),
            'Visa'                 => $translate('Visa'),
            'Destination_Country'  => $translate('Destination Country'),
            'TravelDuration'       => $translate('Travel Duration'),
            'SearchVisa'           => $translate('Search Visa'),
            'SearchCountry'        => $translate('Search country...'),
            'SelectCountry'        => $translate('Select destination country'),
            'Persons'              => $translate('Persons'),
            'Person'               => $translate('Person'),
            'Days'                 => $translate('Days'),
        );
    }
}
