<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FuelStationController extends Controller
{
    /**
     * Find nearby fuel stations
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50', // in kilometers
            'fuel_type' => 'nullable|in:petrol,diesel,super',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 10; // default 10km radius
        $fuelType = $request->fuel_type;
        $limit = $request->limit ?? 20;

        // Get nearby stations
        $stations = FuelStation::active()
            ->nearby($latitude, $longitude, $radius)
            ->take($limit)
            ->get()
            ->map(function ($station) use ($latitude, $longitude, $fuelType) {
                // Add fuel prices
                $prices = $this->getStationPrices($station, $fuelType);
                
                // Add additional information
                return array_merge($station->toArray(), [
                    'distance' => round($station->distance, 2) . ' km',
                    'prices' => $prices,
                    'operating_hours' => $this->getOperatingHours($station),
                    'services' => $this->getStationServices($station),
                    'rating' => $this->getStationRating($station),
                    'wait_time' => $this->estimateWaitTime($station),
                    'is_open' => $this->isStationOpen($station),
                ]);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stations' => $stations,
                'search_params' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => $radius,
                    'fuel_type' => $fuelType,
                    'count' => $stations->count(),
                ],
                'price_summary' => $this->getPriceSummary($stations, $fuelType),
            ]
        ]);
    }

    /**
     * Get station details
     */
    public function show(Request $request, $id)
    {
        $station = FuelStation::active()->find($id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Fuel station not found'
            ], 404);
        }

        // Get user's location if provided
        $userLat = $request->query('latitude');
        $userLng = $request->query('longitude');
        
        $distance = null;
        if ($userLat && $userLng) {
            $distance = $this->calculateDistance(
                $userLat,
                $userLng,
                $station->latitude,
                $station->longitude
            );
        }

        // Get detailed information
        $details = [
            'distance' => $distance ? round($distance, 2) . ' km' : null,
            'prices' => $this->getStationPrices($station),
            'operating_hours' => $this->getOperatingHours($station, true),
            'services' => $this->getStationServices($station, true),
            'amenities' => $this->getStationAmenities($station),
            'payment_methods' => $this->getPaymentMethods($station),
            'rating_details' => $this->getRatingDetails($station),
            'reviews_summary' => $this->getReviewsSummary($station),
            'is_open' => $this->isStationOpen($station),
            'next_open' => $this->getNextOpeningTime($station),
            'popular_times' => $this->getPopularTimes($station),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'station' => $station,
                'details' => $details,
                'voucher_acceptance' => [
                    'accepts_vouchers' => true,
                    'max_amount' => 50000,
                    'supported_fuels' => ['petrol', 'diesel', 'super'],
                    'processing_fee' => 0,
                ],
            ]
        ]);
    }

    /**
     * Search fuel stations
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'city' => 'nullable|string',
            'fuel_type' => 'nullable|in:petrol,diesel,super',
            'has_services' => 'nullable|array',
            'is_open_now' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = FuelStation::active();

        // Search by name, company, or address
        $searchQuery = $request->query;
        $query->where(function ($q) use ($searchQuery) {
            $q->where('name', 'like', "%{$searchQuery}%")
              ->orWhere('company', 'like', "%{$searchQuery}%")
              ->orWhere('address', 'like', "%{$searchQuery}%")
              ->orWhere('city', 'like', "%{$searchQuery}%");
        });

        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        if ($request->filled('is_open_now') && $request->is_open_now) {
            $query->whereIn('id', $this->getOpenStationIds());
        }

        $stations = $query->take(30)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stations' => $stations,
                'search_params' => $request->all(),
                'suggestions' => $this->getSearchSuggestions($request->query),
            ]
        ]);
    }

    /**
     * Get fuel prices
     */
    public function prices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string',
            'fuel_type' => 'nullable|in:petrol,diesel,super',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = FuelStation::active();

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $stations = $query->get();

        // Group prices by fuel type
        $pricesByType = [
            'petrol' => [],
            'diesel' => [],
            'super' => [],
        ];

        foreach ($stations as $station) {
            $stationPrices = $this->getStationPrices($station);
            
            foreach ($stationPrices as $fuelType => $price) {
                if (isset($pricesByType[$fuelType])) {
                    $pricesByType[$fuelType][] = [
                        'station_id' => $station->id,
                        'station_name' => $station->name,
                        'price' => $price,
                        'city' => $station->city,
                    ];
                }
            }
        }

        // Calculate averages
        $averages = [];
        foreach ($pricesByType as $fuelType => $prices) {
            if (!empty($prices)) {
                $priceValues = array_column($prices, 'price');
                $averages[$fuelType] = [
                    'average' => round(array_sum($priceValues) / count($priceValues), 2),
                    'min' => min($priceValues),
                    'max' => max($priceValues),
                    'count' => count($prices),
                ];
            }
        }

        // Get price trends (simulated)
        $trends = $this->getPriceTrends();

        return response()->json([
            'success' => true,
            'data' => [
                'prices_by_station' => $pricesByType,
                'averages' => $averages,
                'trends' => $trends,
                'last_updated' => now()->format('Y-m-d H:i:s'),
                'recommendation' => $this->getPriceRecommendation($averages, $request->fuel_type),
            ]
        ]);
    }

    /**
     * Get station prices (simulated)
     */
    private function getStationPrices($station, $specificType = null)
    {
        // In production, fetch from API or database
        // For now, generate realistic prices based on station location
        
        $basePrices = [
            'petrol' => 180,
            'diesel' => 165,
            'super' => 190,
        ];

        // Add some variation based on station
        $variation = (($station->id % 10) - 5) / 2; // -2.5 to +2.5

        $prices = [];
        
        if ($specificType) {
            $prices[$specificType] = round($basePrices[$specificType] + $variation, 2);
        } else {
            foreach ($basePrices as $type => $basePrice) {
                $prices[$type] = round($basePrice + $variation, 2);
            }
        }

        return $prices;
    }

    /**
     * Get operating hours (simulated)
     */
    private function getOperatingHours($station, $detailed = false)
    {
        $baseHours = [
            'monday' => ['open' => '06:00', 'close' => '22:00'],
            'tuesday' => ['open' => '06:00', 'close' => '22:00'],
            'wednesday' => ['open' => '06:00', 'close' => '22:00'],
            'thursday' => ['open' => '06:00', 'close' => '22:00'],
            'friday' => ['open' => '06:00', 'close' => '23:00'],
            'saturday' => ['open' => '07:00', 'close' => '22:00'],
            'sunday' => ['open' => '08:00', 'close' => '20:00'],
        ];

        if ($detailed) {
            return $baseHours;
        }

        return '6:00 AM - 10:00 PM (Mon-Thu), 6:00 AM - 11:00 PM (Fri), 7:00 AM - 10:00 PM (Sat), 8:00 AM - 8:00 PM (Sun)';
    }

    /**
     * Get station services (simulated)
     */
    private function getStationServices($station, $detailed = false)
    {
        $allServices = [
            'air_tyre' => 'Air & Tyre',
            'car_wash' => 'Car Wash',
            'convenience_store' => 'Convenience Store',
            'restaurant' => 'Restaurant',
            'atm' => 'ATM',
            'toilets' => 'Toilets',
            'ev_charging' => 'EV Charging',
            'lubricants' => 'Lubricants',
        ];

        // Randomly assign services based on station
        $services = array_slice($allServices, 0, rand(3, 6));

        if ($detailed) {
            return $services;
        }

        return array_values($services);
    }

    /**
     * Get station amenities
     */
    private function getStationAmenities($station)
    {
        return [
            'parking' => true,
            'security' => true,
            'lighting' => true,
            'covered_area' => $station->id % 2 == 0,
            'multiple_pumps' => true,
            'truck_friendly' => $station->id % 3 == 0,
            'disabled_access' => true,
        ];
    }

    /**
     * Get payment methods accepted
     */
    private function getPaymentMethods($station)
    {
        return [
            'cash' => true,
            'mpesa' => true,
            'card' => true,
            'vouchers' => true,
            'credit' => false,
            'loyalty_points' => $station->company === 'Shell' || $station->company === 'Total',
        ];
    }

    /**
     * Get station rating (simulated)
     */
    private function getStationRating($station)
    {
        return round(3.5 + (($station->id % 10) / 10), 1); // 3.5 to 4.5
    }

    /**
     * Get rating details
     */
    private function getRatingDetails($station)
    {
        $baseRating = $this->getStationRating($station);
        
        return [
            'overall' => $baseRating,
            'cleanliness' => round($baseRating + 0.1, 1),
            'service' => round($baseRating - 0.1, 1),
            'wait_time' => round($baseRating - 0.2, 1),
            'facilities' => round($baseRating + 0.2, 1),
            'total_reviews' => rand(50, 500),
        ];
    }

    /**
     * Get reviews summary
     */
    private function getReviewsSummary($station)
    {
        return [
            'excellent' => rand(30, 40),
            'good' => rand(20, 30),
            'average' => rand(10, 20),
            'poor' => rand(5, 10),
            'terrible' => rand(1, 5),
        ];
    }

    /**
     * Estimate wait time
     */
    private function estimateWaitTime($station)
    {
        $hour = now()->hour;
        
        if ($hour >= 7 && $hour <= 9) {
            return rand(10, 20); // Morning rush
        } elseif ($hour >= 17 && $hour <= 19) {
            return rand(15, 25); // Evening rush
        } else {
            return rand(5, 15); // Normal hours
        }
    }

    /**
     * Check if station is open
     */
    private function isStationOpen($station)
    {
        $hours = $this->getOperatingHours($station, true);
        $today = strtolower(now()->englishDayOfWeek);
        
        if (!isset($hours[$today])) {
            return false;
        }
        
        $openTime = strtotime($hours[$today]['open']);
        $closeTime = strtotime($hours[$today]['close']);
        $currentTime = strtotime(now()->format('H:i'));
        
        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    /**
     * Get next opening time
     */
    private function getNextOpeningTime($station)
    {
        if ($this->isStationOpen($station)) {
            return null;
        }
        
        $hours = $this->getOperatingHours($station, true);
        $today = strtolower(now()->englishDayOfWeek);
        $tomorrow = strtolower(now()->addDay()->englishDayOfWeek);
        
        if (isset($hours[$tomorrow])) {
            return "Tomorrow at " . $hours[$tomorrow]['open'];
        }
        
        return "Next opening: " . $hours['monday']['open'];
    }

    /**
     * Get popular times (simulated)
     */
    private function getPopularTimes($station)
    {
        $times = [];
        for ($hour = 6; $hour <= 22; $hour++) {
            $busyness = rand(20, 100);
            
            // Peak hours are busier
            if (($hour >= 7 && $hour <= 9) || ($hour >= 17 && $hour <= 19)) {
                $busyness = rand(70, 100);
            }
            
            $times[] = [
                'hour' => sprintf('%02d:00', $hour),
                'busyness' => $busyness,
                'description' => $busyness < 40 ? 'Not busy' : 
                               ($busyness < 70 ? 'Usually not busy' : 
                               ($busyness < 90 ? 'Usually busy' : 'Very busy')),
            ];
        }
        
        return $times;
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // kilometers
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Get price summary
     */
    private function getPriceSummary($stations, $fuelType = null)
    {
        if ($stations->isEmpty()) {
            return null;
        }

        $prices = [];
        foreach ($stations as $station) {
            $stationPrices = $this->getStationPrices($station, $fuelType);
            
            if ($fuelType) {
                $prices[] = $stationPrices[$fuelType] ?? null;
            } else {
                foreach ($stationPrices as $price) {
                    $prices[] = $price;
                }
            }
        }

        $prices = array_filter($prices);

        if (empty($prices)) {
            return null;
        }

        return [
            'average' => round(array_sum($prices) / count($prices), 2),
            'minimum' => min($prices),
            'maximum' => max($prices),
            'median' => $this->calculateMedian($prices),
        ];
    }

    /**
     * Calculate median
     */
    private function calculateMedian($array)
    {
        sort($array);
        $count = count($array);
        $middle = floor(($count - 1) / 2);
        
        if ($count % 2) {
            return $array[$middle];
        } else {
            return ($array[$middle] + $array[$middle + 1]) / 2;
        }
    }

    /**
     * Get price trends (simulated)
     */
    private function getPriceTrends()
    {
        $today = now();
        $trends = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'petrol' => round(175 + rand(-5, 5) + ($i * 0.1), 2),
                'diesel' => round(160 + rand(-5, 5) + ($i * 0.1), 2),
                'super' => round(185 + rand(-5, 5) + ($i * 0.1), 2),
            ];
        }
        
        return $trends;
    }

    /**
     * Get price recommendation
     */
    private function getPriceRecommendation($averages, $fuelType = null)
    {
        if (!$fuelType) {
            return 'Check specific fuel type for best prices';
        }
        
        if (!isset($averages[$fuelType])) {
            return 'No price data available';
        }
        
        $avg = $averages[$fuelType]['average'];
        $min = $averages[$fuelType]['min'];
        $max = $averages[$fuelType]['max'];
        
        if ($min < $avg * 0.95) {
            return 'Good deals available (' . round(($avg - $min) / $avg * 100, 1) . '% below average)';
        } elseif ($max > $avg * 1.05) {
            return 'Prices are high, consider waiting or searching nearby';
        } else {
            return 'Prices are average for the area';
        }
    }

    /**
     * Get open station IDs (simulated)
     */
    private function getOpenStationIds()
    {
        // In production, query based on operating hours
        // For now, return random stations
        return FuelStation::active()->inRandomOrder()->take(10)->pluck('id')->toArray();
    }

    /**
     * Get search suggestions
     */
    private function getSearchSuggestions($query)
    {
        $suggestions = [
            'stations' => FuelStation::where('name', 'like', "%{$query}%")
                ->orWhere('company', 'like', "%{$query}%")
                ->take(5)
                ->pluck('name')
                ->toArray(),
            'cities' => FuelStation::where('city', 'like', "%{$query}%")
                ->distinct()
                ->take(5)
                ->pluck('city')
                ->toArray(),
            'companies' => FuelStation::where('company', 'like', "%{$query}%")
                ->distinct()
                ->take(5)
                ->pluck('company')
                ->toArray(),
        ];
        
        return array_filter($suggestions);
    }
}