<?php

/**
 * Haversine formula to calculate the distance between two points given their latitude and longitude.
 *
 * @param float $lat1 Latitude of the first point.
 * @param float $lon1 Longitude of the first point.
 * @param float $lat2 Latitude of the second point.
 * @param float $lon2 Longitude of the second point.
 * @return float Distance in kilometers.
 */
function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371; // Earth's radius in kilometers

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c; // Distance in kilometers
}
