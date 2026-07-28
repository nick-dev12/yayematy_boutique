<?php
/**
 * Service de localisation GPS (navigation / partage).
 * Sous-ensemble adapté depuis poid_lourd pour l'admin commandes.
 */

function geo_parse_coord($value): ?float
{
    if ($value === null || $value === '' || is_array($value)) {
        return null;
    }
    $value = str_replace(',', '.', trim((string) $value));
    if (!is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

function geo_coords_valid(?float $lat, ?float $lng): bool
{
    if ($lat === null || $lng === null) {
        return false;
    }
    if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
        return false;
    }
    if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
        return false;
    }
    return true;
}

function geo_nav_google_maps_dir(float $lat, float $lng): string
{
    return 'https://www.google.com/maps/dir/?api=1&destination='
        . rawurlencode($lat . ',' . $lng) . '&travelmode=driving';
}
