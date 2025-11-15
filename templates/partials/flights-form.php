<?php
/**
 * Flights Form Partial
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

<!-- Trip Type Selection -->
<div class="tw-trip-type">
    <label class="tw-radio-label">
        <input type="radio" name="tripType" value="round-trip" checked>
        <span><?php echo esc_html($translate('Round Trip')); ?></span>
    </label>
    <label class="tw-radio-label">
        <input type="radio" name="tripType" value="one-way">
        <span><?php echo esc_html($translate('One Way')); ?></span>
    </label>
    <label class="tw-radio-label">
        <input type="radio" name="tripType" value="multi-city">
        <span><?php echo esc_html($translate('Multi City')); ?></span>
    </label>
</div>

<!-- Form Fields -->
<div class="tw-form-row horizontal">
    <!-- From -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('From')); ?></label>
        <div class="tw-input-wrapper tw-suggest">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <input type="text" class="tw-input" id="flightFrom" placeholder="<?php echo esc_attr($translate('City or Airport')); ?>">
            <div class="tw-suggest-list" id="fromSuggest" style="display:none;"></div>
        </div>
    </div>

    <!-- To -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('To')); ?></label>
        <div class="tw-input-wrapper tw-suggest">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <input type="text" class="tw-input" id="flightTo" placeholder="<?php echo esc_attr($translate('City or Airport')); ?>">
            <div class="tw-suggest-list" id="toSuggest" style="display:none;"></div>
        </div>
    </div>

    <!-- Depart Date -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Depart')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="date" class="tw-input" id="departDate">
        </div>
    </div>

    <!-- Return Date -->
    <div class="tw-field" id="returnDateField">
        <label class="tw-label"><?php echo esc_html($translate('Return')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="date" class="tw-input" id="returnDate">
        </div>
    </div>

    <!-- Passengers -->
    <div class="tw-field tw-passenger-field">
        <label class="tw-label"><?php echo esc_html($translate('Passengers')); ?></label>
        <div class="tw-input-wrapper">
            <svg class="tw-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <input type="text" readonly class="tw-input" id="passengerDisplay" style="cursor:pointer;" value="1 <?php echo esc_attr($translate('Passenger')); ?>, <?php echo esc_attr($translate('Economy')); ?>">
        </div>

        <!-- Passenger Dropdown -->
        <div class="tw-passenger-dropdown" id="passengerDropdown">
            <div class="tw-passenger-row">
                <p class="tw-passenger-label"><?php echo esc_html($translate('Adults')); ?></p>
                <div class="tw-passenger-controls">
                    <button type="button" class="tw-passenger-btn" id="adultsDec">–</button>
                    <span class="tw-passenger-count" id="adultsCount">1</span>
                    <button type="button" class="tw-passenger-btn" id="adultsInc">+</button>
                </div>
            </div>
            <div class="tw-passenger-row">
                <p class="tw-passenger-label"><?php echo esc_html($translate('Children')); ?></p>
                <div class="tw-passenger-controls">
                    <button type="button" class="tw-passenger-btn" id="childrenDec">–</button>
                    <span class="tw-passenger-count" id="childrenCount">0</span>
                    <button type="button" class="tw-passenger-btn" id="childrenInc">+</button>
                </div>
            </div>
            <div class="tw-passenger-row">
                <p class="tw-passenger-label"><?php echo esc_html($translate('Infants')); ?></p>
                <div class="tw-passenger-controls">
                    <button type="button" class="tw-passenger-btn" id="infantsDec">–</button>
                    <span class="tw-passenger-count" id="infantsCount">0</span>
                    <button type="button" class="tw-passenger-btn" id="infantsInc">+</button>
                </div>
            </div>
            <div class="tw-travel-class">
                <div class="tw-label"><?php echo esc_html($translate('Travel Class')); ?></div>
                <div class="tw-radio-options">
                    <label class="tw-radio-option">
                        <input type="radio" name="flightClassDropdown" value="economy" checked>
                        <?php echo esc_html($translate('Economy')); ?>
                    </label>
                    <label class="tw-radio-option">
                        <input type="radio" name="flightClassDropdown" value="premium-economy">
                        <?php echo esc_html($translate('Premium Economy')); ?>
                    </label>
                    <label class="tw-radio-option">
                        <input type="radio" name="flightClassDropdown" value="business">
                        <?php echo esc_html($translate('Business')); ?>
                    </label>
                    <label class="tw-radio-option">
                        <input type="radio" name="flightClassDropdown" value="first">
                        <?php echo esc_html($translate('First Class')); ?>
                    </label>
                </div>
            </div>
            <div class="tw-passenger-footer">
                <button type="button" class="tw-done-btn" id="passengersDone"><?php echo esc_html($translate('Done')); ?></button>
            </div>
        </div>

        <!-- Hidden fields for form submission -->
        <input type="hidden" id="hidAdults" value="1"/>
        <input type="hidden" id="hidChildren" value="0"/>
        <input type="hidden" id="hidInfants" value="0"/>
        <input type="hidden" id="flightClass" value="economy"/>
    </div>

    <!-- Class -->
    <div class="tw-field">
        <label class="tw-label"><?php echo esc_html($translate('Class')); ?></label>
        <select class="tw-select no-icon" id="flightClassMirror">
            <option value="economy" selected><?php echo esc_html($translate('Economy')); ?></option>
            <option value="premium-economy"><?php echo esc_html($translate('Premium Economy')); ?></option>
            <option value="business"><?php echo esc_html($translate('Business')); ?></option>
            <option value="first"><?php echo esc_html($translate('First Class')); ?></option>
        </select>
    </div>
</div>

<!-- Search Button -->
<button class="tw-search-btn" id="btnSearchFlights">
    <svg class="tw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <?php echo esc_html($translate('Search Flights')); ?>
</button>

<!-- Additional Services -->
<div class="tw-services">
    <label class="tw-checkbox-label">
        <input type="checkbox" id="cipService" value="cip">
        <span><?php echo esc_html($translate('CIP')); ?></span>
    </label>
    <label class="tw-checkbox-label">
        <input type="checkbox" id="insuranceService" value="insurance">
        <span><?php echo esc_html($translate('Travel Insurance')); ?></span>
    </label>
</div>
