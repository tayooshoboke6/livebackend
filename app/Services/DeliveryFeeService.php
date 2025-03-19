<?php

namespace App\Services;

use App\Models\StoreAddress;
use App\Models\DeliverySettings;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class DeliveryFeeService
{
    /**
     * Calculate delivery fee based on distance, order subtotal, and store settings
     *
     * @param float $subtotal Order subtotal
     * @param array $customerLocation Customer's location [latitude, longitude]
     * @param int|null $storeId Store ID (optional)
     * @return array Delivery fee details
     */
    public function calculateDeliveryFee(float $subtotal, array $customerLocation, ?int $storeId = null): array
    {
        try {
            // Get the appropriate store
            $store = $this->getStore($storeId);
            
            if (!$store) {
                return $this->getErrorResponse("No valid store found for delivery");
            }
            
            // Check if the customer is within delivery zone
            $withinZone = $this->isWithinDeliveryZone($customerLocation, $store);
            
            if (!$withinZone) {
                return $this->getErrorResponse("Sorry, we don't currently deliver to your location.");
            }
            
            // Calculate distance to store
            $storeLocation = [$store->latitude, $store->longitude];
            $distanceInKm = $this->calculateDistance($customerLocation, $storeLocation);
            
            // Get global delivery settings
            $globalSettings = $this->getGlobalSettings();
            
            // Get store delivery settings
            $baseFee = $store->delivery_base_fee ?? $globalSettings->base_fee ?? 500; // Default: ₦500 base fee
            $feePerKm = $store->delivery_fee_per_km ?? $globalSettings->fee_per_km ?? 100; // Default: ₦100 per km
            $freeThreshold = $store->delivery_free_threshold ?? $globalSettings->free_threshold ?? 10000; // Default: Free for orders over ₦10,000
            $minOrder = $store->delivery_min_order ?? $globalSettings->min_order ?? 0; // Default: No minimum order
            
            // Check if order meets minimum requirement
            if ($subtotal < $minOrder) {
                return $this->getErrorResponse(
                    "Minimum order for delivery is ₦" . number_format($minOrder) . ". Please add more items.",
                    $distanceInKm
                );
            }
            
            // Calculate fee
            $fee = $baseFee;
            
            // Distance fee (fee per km after first 2km)
            $distanceFee = $distanceInKm <= 2 ? 0 : ceil($distanceInKm - 2) * $feePerKm;
            $fee += $distanceFee;
            
            // Free delivery for orders over threshold
            if ($subtotal >= $freeThreshold) {
                $fee = 0;
            }
            
            // Estimate delivery time (5 minutes + 3 minutes per km)
            $estimatedTime = 5 + ceil($distanceInKm * 3);
            
            // Prepare message based on fee
            $message = $fee === 0
                ? "Free delivery for your order!"
                : "Delivery fee: ₦" . number_format($fee) . " (" . number_format($distanceInKm, 1) . " km)";
            
            return [
                'fee' => $fee,
                'distance' => $distanceInKm,
                'currency' => 'NGN',
                'estimatedTime' => $estimatedTime,
                'isDeliveryAvailable' => true,
                'message' => $message,
                'store_id' => $store->id
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating delivery fee: ' . $e->getMessage());
            return $this->getErrorResponse("Unable to calculate delivery fee");
        }
    }
    
    /**
     * Get the appropriate store for delivery
     *
     * @param int|null $storeId Store ID
     * @return StoreAddress|null
     */
    protected function getStore(?int $storeId): ?StoreAddress
    {
        if ($storeId) {
            return StoreAddress::find($storeId);
        }
        
        // If no store ID provided, get the first active store
        return StoreAddress::where('is_active', true)
            ->where('delivery_enabled', true)
            ->first();
    }
    
    /**
     * Get global delivery settings
     *
     * @return DeliverySettings|null
     */
    protected function getGlobalSettings(): ?DeliverySettings
    {
        return DeliverySettings::where('is_global', true)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Check if a location is within a store's delivery zone
     *
     * @param array $customerLocation [latitude, longitude]
     * @param StoreAddress $store
     * @return bool
     */
    protected function isWithinDeliveryZone(array $customerLocation, StoreAddress $store): bool
    {
        // Get global settings
        $globalSettings = $this->getGlobalSettings();
        $maxDistance = $globalSettings ? $globalSettings->max_distance : 20; // Default: 20km
        
        // Use store-specific radius if available, otherwise use global max distance
        $radius = $store->delivery_radius_km ?? $maxDistance;
        
        if (!$radius) {
            return false;
        }
        
        $storeLocation = [$store->latitude, $store->longitude];
        $distance = $this->calculateDistance($customerLocation, $storeLocation);
        
        return $distance <= $radius;
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     *
     * @param array $point1 [latitude, longitude]
     * @param array $point2 [latitude, longitude]
     * @return float Distance in kilometers
     */
    protected function calculateDistance(array $point1, array $point2): float
    {
        $lat1 = $point1[0];
        $lon1 = $point1[1];
        $lat2 = $point2[0];
        $lon2 = $point2[1];
        
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
    
    /**
     * Get error response format
     *
     * @param string $message Error message
     * @param float $distance Distance in km (optional)
     * @return array
     */
    protected function getErrorResponse(string $message, float $distance = 0): array
    {
        return [
            'fee' => 0,
            'distance' => $distance,
            'currency' => 'NGN',
            'estimatedTime' => 0,
            'isDeliveryAvailable' => false,
            'message' => $message
        ];
    }
}
