<?php
/**
 * Travel Widget Template
 *
 * This template renders the HTML structure for the travel booking widget
 *
 * @package Alibeyg_Citynet_Bridge
 * @since 0.5.0
 */

defined('ABSPATH') || exit;

// Get translation function
$translate = function($string) {
    if (function_exists('pll__')) {
        return pll__($string);
    }
    return __($string, 'alibeyg-citynet');
};
?>

<div class="travel-widget-container">
    <!-- Tab Navigation -->
    <div class="tw-tabs">
        <button class="tw-tab active" data-tab="flights">
            <svg class="tw-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            <?php echo esc_html($translate('Flights')); ?>
        </button>
        <button class="tw-tab" data-tab="hotels">
            <svg class="tw-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <?php echo esc_html($translate('Hotels')); ?>
        </button>
        <button class="tw-tab" data-tab="visa">
            <svg class="tw-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <?php echo esc_html($translate('Visa')); ?>
        </button>
    </div>

    <!-- Tab Content -->
    <div class="tw-content">
        <!-- Flights Panel -->
        <div class="tw-tab-panel active" id="flights-panel">
            <?php include __DIR__ . '/partials/flights-form.php'; ?>
        </div>

        <!-- Hotels Panel -->
        <div class="tw-tab-panel" id="hotels-panel">
            <?php include __DIR__ . '/partials/hotels-form.php'; ?>
        </div>

        <!-- Visa Panel -->
        <div class="tw-tab-panel" id="visa-panel">
            <?php include __DIR__ . '/partials/visa-form.php'; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="tw-footer">
        <?php echo esc_html($translate('Powered by Travel Booking Engine')); ?>
    </div>
</div>
