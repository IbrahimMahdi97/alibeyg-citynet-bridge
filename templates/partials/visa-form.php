<?php
/**
 * Visa Form Partial
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
<div class="tw-form-row">
    <!-- Country Selector -->
    <div class="tw-field" style="position:relative;">
        <label class="tw-label"><?php echo esc_html($translate('Destination Country')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
            </svg>
            <input type="text" readonly class="tw-input" id="visaCountryDisplay" style="cursor:pointer;" placeholder="<?php echo esc_attr($translate('Select destination country')); ?>">
            <input type="hidden" id="visaCountryCode" value="">
        </div>

        <!-- Country Dropdown -->
        <div class="tw-country-dropdown" id="countryDropdown">
            <div class="tw-country-search">
                <input type="text" id="countrySearchInput" placeholder="<?php echo esc_attr($translate('Search country...')); ?>">
            </div>
            <div class="tw-country-list" id="countryList"></div>
        </div>
    </div>

    <!-- Travel Duration -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Travel Duration')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <select class="tw-select" id="visaDuration">
                <option value="7">7 <?php echo esc_html($translate('Days')); ?></option>
                <option value="14">14 <?php echo esc_html($translate('Days')); ?></option>
                <option value="30" selected>30 <?php echo esc_html($translate('Days')); ?></option>
                <option value="60">60 <?php echo esc_html($translate('Days')); ?></option>
                <option value="90">90 <?php echo esc_html($translate('Days')); ?></option>
            </select>
        </div>
    </div>

    <!-- Persons Selector -->
    <div class="tw-field" style="position:relative;">
        <label class="tw-label"><?php echo esc_html($translate('Persons')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <input type="text" readonly class="tw-input" id="visaPersonDisplay" style="cursor:pointer;" value="1 <?php echo esc_attr($translate('Person')); ?>">
        </div>

        <!-- Person Dropdown -->
        <div class="tw-person-dropdown" id="personDropdown">
            <div class="tw-passenger-row">
                <p class="tw-passenger-label"><?php echo esc_html($translate('Adults')); ?></p>
                <div class="tw-passenger-controls">
                    <button type="button" class="tw-passenger-btn" id="visaAdultsDec">–</button>
                    <span class="tw-passenger-count" id="visaAdultsCount">1</span>
                    <button type="button" class="tw-passenger-btn" id="visaAdultsInc">+</button>
                </div>
            </div>
            <div class="tw-passenger-row">
                <p class="tw-passenger-label"><?php echo esc_html($translate('Children')); ?></p>
                <div class="tw-passenger-controls">
                    <button type="button" class="tw-passenger-btn" id="visaChildrenDec">–</button>
                    <span class="tw-passenger-count" id="visaChildrenCount">0</span>
                    <button type="button" class="tw-passenger-btn" id="visaChildrenInc">+</button>
                </div>
            </div>
            <div class="tw-passenger-footer">
                <button type="button" class="tw-done-btn" id="visaPersonDone"><?php echo esc_html($translate('Done')); ?></button>
            </div>
        </div>

        <input type="hidden" id="hidVisaAdults" value="1"/>
        <input type="hidden" id="hidVisaChildren" value="0"/>
    </div>
</div>

<!-- Search Button -->
<button class="tw-search-btn" id="btnSearchVisa">
    <svg class="tw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <?php echo esc_html($translate('Search CIP')); ?>
</button>
