<?php
/**
 * Tier-1 country list (39 total) used when an offer's allowed_geos is
 * "Tier-1 (39 Countries)". Grouped by region for the viewer modal.
 * ISO 3166 two-letter codes feed flagcdn.com for flag images so Windows
 * users (who don't render flag emojis) still see actual flags.
 */

const GEO_OPTIONS = [
    'US Only',
    'Tier-1 Default',
    'Tier-1 (39 Countries)',
];

const TIER1_COUNTRIES = [
    'United States & Canada' => [
        ['code' => 'us', 'flag' => '🇺🇸', 'name' => 'United States'],
        ['code' => 'ca', 'flag' => '🇨🇦', 'name' => 'Canada'],
    ],
    'UK & Ireland' => [
        ['code' => 'gb', 'flag' => '🇬🇧', 'name' => 'United Kingdom'],
        ['code' => 'ie', 'flag' => '🇮🇪', 'name' => 'Ireland'],
    ],
    'Northern Europe' => [
        ['code' => 'se', 'flag' => '🇸🇪', 'name' => 'Sweden'],
        ['code' => 'dk', 'flag' => '🇩🇰', 'name' => 'Denmark'],
        ['code' => 'no', 'flag' => '🇳🇴', 'name' => 'Norway'],
        ['code' => 'fi', 'flag' => '🇫🇮', 'name' => 'Finland'],
        ['code' => 'is', 'flag' => '🇮🇸', 'name' => 'Iceland'],
        ['code' => 'ee', 'flag' => '🇪🇪', 'name' => 'Estonia'],
        ['code' => 'lv', 'flag' => '🇱🇻', 'name' => 'Latvia'],
        ['code' => 'lt', 'flag' => '🇱🇹', 'name' => 'Lithuania'],
    ],
    'Western Europe' => [
        ['code' => 'de', 'flag' => '🇩🇪', 'name' => 'Germany'],
        ['code' => 'fr', 'flag' => '🇫🇷', 'name' => 'France'],
        ['code' => 'ch', 'flag' => '🇨🇭', 'name' => 'Switzerland'],
        ['code' => 'nl', 'flag' => '🇳🇱', 'name' => 'Netherlands'],
        ['code' => 'at', 'flag' => '🇦🇹', 'name' => 'Austria'],
        ['code' => 'be', 'flag' => '🇧🇪', 'name' => 'Belgium'],
        ['code' => 'lu', 'flag' => '🇱🇺', 'name' => 'Luxembourg'],
        ['code' => 'li', 'flag' => '🇱🇮', 'name' => 'Liechtenstein'],
        ['code' => 'mc', 'flag' => '🇲🇨', 'name' => 'Monaco'],
    ],
    'Southern Europe' => [
        ['code' => 'it', 'flag' => '🇮🇹', 'name' => 'Italy'],
        ['code' => 'es', 'flag' => '🇪🇸', 'name' => 'Spain'],
        ['code' => 'pt', 'flag' => '🇵🇹', 'name' => 'Portugal'],
        ['code' => 'gr', 'flag' => '🇬🇷', 'name' => 'Greece'],
        ['code' => 'mt', 'flag' => '🇲🇹', 'name' => 'Malta'],
        ['code' => 'cy', 'flag' => '🇨🇾', 'name' => 'Cyprus'],
        ['code' => 'sm', 'flag' => '🇸🇲', 'name' => 'San Marino'],
        ['code' => 'va', 'flag' => '🇻🇦', 'name' => 'Vatican City'],
        ['code' => 'ad', 'flag' => '🇦🇩', 'name' => 'Andorra'],
        ['code' => 'gi', 'flag' => '🇬🇮', 'name' => 'Gibraltar'],
    ],
    'Australasia' => [
        ['code' => 'au', 'flag' => '🇦🇺', 'name' => 'Australia'],
        ['code' => 'nz', 'flag' => '🇳🇿', 'name' => 'New Zealand'],
        ['code' => 'pg', 'flag' => '🇵🇬', 'name' => 'Papua New Guinea'],
        ['code' => 'fj', 'flag' => '🇫🇯', 'name' => 'Fiji'],
        ['code' => 'sb', 'flag' => '🇸🇧', 'name' => 'Solomon Islands'],
        ['code' => 'vu', 'flag' => '🇻🇺', 'name' => 'Vanuatu'],
        ['code' => 'ws', 'flag' => '🇼🇸', 'name' => 'Samoa'],
        ['code' => 'to', 'flag' => '🇹🇴', 'name' => 'Tonga'],
    ],
];
