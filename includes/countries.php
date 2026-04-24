<?php
/**
 * Tier-1 country list (39 total) used when an offer's allowed_geos is
 * "Tier-1 (39 Countries)". Grouped by region for the viewer modal.
 */

const GEO_OPTIONS = [
    'US Only',
    'Tier-1 Default',
    'Tier-1 (39 Countries)',
];

const TIER1_COUNTRIES = [
    'United States & Canada' => [
        ['flag' => '🇺🇸', 'name' => 'United States'],
        ['flag' => '🇨🇦', 'name' => 'Canada'],
    ],
    'UK & Ireland' => [
        ['flag' => '🇬🇧', 'name' => 'United Kingdom'],
        ['flag' => '🇮🇪', 'name' => 'Ireland'],
    ],
    'Northern Europe' => [
        ['flag' => '🇸🇪', 'name' => 'Sweden'],
        ['flag' => '🇩🇰', 'name' => 'Denmark'],
        ['flag' => '🇳🇴', 'name' => 'Norway'],
        ['flag' => '🇫🇮', 'name' => 'Finland'],
        ['flag' => '🇮🇸', 'name' => 'Iceland'],
        ['flag' => '🇪🇪', 'name' => 'Estonia'],
        ['flag' => '🇱🇻', 'name' => 'Latvia'],
        ['flag' => '🇱🇹', 'name' => 'Lithuania'],
    ],
    'Western Europe' => [
        ['flag' => '🇩🇪', 'name' => 'Germany'],
        ['flag' => '🇫🇷', 'name' => 'France'],
        ['flag' => '🇨🇭', 'name' => 'Switzerland'],
        ['flag' => '🇳🇱', 'name' => 'Netherlands'],
        ['flag' => '🇦🇹', 'name' => 'Austria'],
        ['flag' => '🇧🇪', 'name' => 'Belgium'],
        ['flag' => '🇱🇺', 'name' => 'Luxembourg'],
        ['flag' => '🇱🇮', 'name' => 'Liechtenstein'],
        ['flag' => '🇲🇨', 'name' => 'Monaco'],
    ],
    'Southern Europe' => [
        ['flag' => '🇮🇹', 'name' => 'Italy'],
        ['flag' => '🇪🇸', 'name' => 'Spain'],
        ['flag' => '🇵🇹', 'name' => 'Portugal'],
        ['flag' => '🇬🇷', 'name' => 'Greece'],
        ['flag' => '🇲🇹', 'name' => 'Malta'],
        ['flag' => '🇨🇾', 'name' => 'Cyprus'],
        ['flag' => '🇸🇲', 'name' => 'San Marino'],
        ['flag' => '🇻🇦', 'name' => 'Vatican City'],
        ['flag' => '🇦🇩', 'name' => 'Andorra'],
        ['flag' => '🇬🇮', 'name' => 'Gibraltar'],
    ],
    'Australasia' => [
        ['flag' => '🇦🇺', 'name' => 'Australia'],
        ['flag' => '🇳🇿', 'name' => 'New Zealand'],
        ['flag' => '🇵🇬', 'name' => 'Papua New Guinea'],
        ['flag' => '🇫🇯', 'name' => 'Fiji'],
        ['flag' => '🇸🇧', 'name' => 'Solomon Islands'],
        ['flag' => '🇻🇺', 'name' => 'Vanuatu'],
        ['flag' => '🇼🇸', 'name' => 'Samoa'],
        ['flag' => '🇹🇴', 'name' => 'Tonga'],
    ],
];
