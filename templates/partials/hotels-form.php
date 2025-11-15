<?php
/**
 * Hotels Form Partial
 *
 * @package Alibeyg_Citynet_Bridge
 */

defined('ABSPATH') || exit;

$translate = function($string) {
    if (function_exists('pll__')) {
        return pll__($string);
    }
    return __($string, 'alibeyg-citynet');
};
?>

<!-- Form Fields -->
<div class="tw-form-row hotel-horizontal">
    <!-- Destination -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Destination')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <input type="text" class="tw-input" id="hotelDestination" placeholder="<?php echo esc_attr($translate('City, Hotel, Place')); ?>">
        </div>
    </div>

    <!-- Check-in -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Check-in')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="date" class="tw-input" id="checkIn">
        </div>
    </div>

    <!-- Check-out -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Check-out')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="date" class="tw-input" id="checkOut">
        </div>
    </div>

    <!-- Rooms -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Rooms')); ?></label>
        <select class="tw-select no-icon" id="rooms">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
        </select>
    </div>

    <!-- Guests -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Guests')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <select class="tw-select" id="guests">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
            </select>
        </div>
    </div>
</div>

<!-- Search Button -->
<button class="tw-search-btn" id="btnSearchHotels">
    <svg class="tw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <?php echo esc_html($translate('Search Hotels')); ?>
</button>
