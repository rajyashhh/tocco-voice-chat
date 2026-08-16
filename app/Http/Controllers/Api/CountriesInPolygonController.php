<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CountriesInPolygonController extends Controller
{
    private $maxPoints = 80;
    private $batchSize = 20; 
    private $requestTimeout = 8; 
    
    public function getCountriesInPolygon(Request $request)
    {
        $coordinates = $request->input('coordinates');
        
        if (empty($coordinates) || count($coordinates) < 3) {
            return response()->json(['countries' => []]);
        }

        $countries = $this->detectCountriesInPolygon($coordinates);
        return response()->json(['countries' => $countries]);
    }

    private function detectCountriesInPolygon($polygon)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $countryCodes = [];
        $bounds = $this->calculateBounds($polygon);
        
        $areaSize = ($bounds['maxLat'] - $bounds['minLat']) * ($bounds['maxLng'] - $bounds['minLng']);
        
        $gridSize = $areaSize > 1000 ? 50 : ($areaSize > 100 ? 40 : 30);
        
        
        $testPoints = $this->generateOptimizedGrid($polygon, $bounds, $gridSize);
        
        $countryCodes = $this->fetchCountriesParallel($testPoints, $apiKey);
        
        if ($areaSize > 1000 && count($countryCodes) < 15) {
            $additionalPoints = $this->generateTargetedPoints($polygon, $bounds, 30);
            $additionalCountries = $this->fetchCountriesParallel($additionalPoints, $apiKey);
            $countryCodes = array_unique(array_merge($countryCodes, $additionalCountries));
        }
        return $this->getCountriesData($countryCodes);
    }

    private function generateOptimizedGrid($polygon, $bounds, $gridSize)
    {
        $testPoints = [];
        $latStep = ($bounds['maxLat'] - $bounds['minLat']) / $gridSize;
        $lngStep = ($bounds['maxLng'] - $bounds['minLng']) / $gridSize;
    
        $centerLat = ($bounds['minLat'] + $bounds['maxLat']) / 2;
        $centerLng = ($bounds['minLng'] + $bounds['maxLng']) / 2;
        if ($this->isPointInPolygon($centerLat, $centerLng, $polygon)) {
            $testPoints[] = ['lat' => $centerLat, 'lng' => $centerLng];
        }
    
        $corners = [
            ['lat' => $bounds['minLat'] + $latStep * 3, 'lng' => $bounds['minLng'] + $lngStep * 3],
            ['lat' => $bounds['maxLat'] - $latStep * 3, 'lng' => $bounds['minLng'] + $lngStep * 3],
            ['lat' => $bounds['minLat'] + $latStep * 3, 'lng' => $bounds['maxLng'] - $lngStep * 3],
            ['lat' => $bounds['maxLat'] - $latStep * 3, 'lng' => $bounds['maxLng'] - $lngStep * 3],
        ];
        
        foreach ($corners as $corner) {
            if ($this->isPointInPolygon($corner['lat'], $corner['lng'], $polygon)) {
                $testPoints[] = $corner;
            }
        }

        for ($i = 1; $i <= 4; $i++) {
            $ratio = $i / 5.0;
            $axisPoints = [
                ['lat' => $bounds['minLat'] + ($bounds['maxLat'] - $bounds['minLat']) * $ratio, 'lng' => $centerLng],
                ['lat' => $centerLat, 'lng' => $bounds['minLng'] + ($bounds['maxLng'] - $bounds['minLng']) * $ratio],
            ];
            
            foreach ($axisPoints as $point) {
                if ($this->isPointInPolygon($point['lat'], $point['lng'], $polygon)) {
                    $testPoints[] = $point;
                }
            }
        }

        for ($lat = $bounds['minLat']; $lat <= $bounds['maxLat']; $lat += $latStep) {
            for ($lng = $bounds['minLng']; $lng <= $bounds['maxLng']; $lng += $lngStep) {
                if ($this->isPointInPolygon($lat, $lng, $polygon)) {
                    if (!$this->isDuplicatePoint(['lat' => $lat, 'lng' => $lng], $testPoints, 0.1)) {
                        $testPoints[] = ['lat' => $lat, 'lng' => $lng];
                    }
                    
                    if (count($testPoints) >= $this->maxPoints) {
                        return $testPoints;
                    }
                }
            }
        }
    
        return $testPoints;
    }

    private function generateTargetedPoints($polygon, $bounds, $count)
    {
        $points = [];
        $attempts = 0;
        $maxAttempts = $count * 3;
        
        while (count($points) < $count && $attempts < $maxAttempts) {
            $randomLat = $bounds['minLat'] + (($bounds['maxLat'] - $bounds['minLat']) * (mt_rand(0, 1000) / 1000));
            $randomLng = $bounds['minLng'] + (($bounds['maxLng'] - $bounds['minLng']) * (mt_rand(0, 1000) / 1000));
            
            if ($this->isPointInPolygon($randomLat, $randomLng, $polygon)) {
                if (!$this->isDuplicatePoint(['lat' => $randomLat, 'lng' => $randomLng], $points, 0.5)) {
                    $points[] = ['lat' => $randomLat, 'lng' => $randomLng];
                }
            }
            
            $attempts++;
        }

        return $points;
    }

    private function fetchCountriesParallel($points, $apiKey)
    {
        $countryCodes = [];
        $chunks = array_chunk($points, $this->batchSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $promises = [];
            
            foreach ($chunk as $index => $point) {
                $cacheKey = 'geocode_' . round($point['lat'], 2) . '_' . round($point['lng'], 2);
                
                $cachedData = Cache::get($cacheKey);
                if ($cachedData) {
                    $this->extractCountryCodes($cachedData, $countryCodes, $index);
                    continue;
                }
                
                $promises[] = Http::timeout($this->requestTimeout)
                    ->async()
                    ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'latlng' => $point['lat'] . ',' . $point['lng'],
                        'key' => $apiKey,
                        'result_type' => 'country'
                    ])
                    ->then(function ($response) use ($cacheKey, &$countryCodes, $index, $point) {
                        if ($response->successful()) {
                            $data = $response->json();
                            Cache::put($cacheKey, $data, 3600);
                            $this->extractCountryCodes($data, $countryCodes, $index);
                        }
                    })
                    ->otherwise(function ($exception) use ($index) {
                        // Log::warning("Failed at point {$index}: " . $exception->getMessage());
                    });
            }
            
            if (!empty($promises)) {
                try {
                    \GuzzleHttp\Promise\Utils::settle($promises)->wait();
                } catch (\Exception $e) {
                    // Log::error("Batch {$chunkIndex} error: " . $e->getMessage());
                }
                
                usleep(200000); 
            }
        }

        return array_unique($countryCodes);
    }

    private function extractCountryCodes($data, &$countryCodes, $index)
    {
        if (!empty($data['results'])) {
            foreach ($data['results'] as $result) {
                foreach ($result['address_components'] as $component) {
                    if (in_array('country', $component['types'])) {
                        $countryCode = $component['short_name'];
                        
                        if (!in_array($countryCode, $countryCodes)) {
                            $countryCodes[] = $countryCode;
                        }
                    }
                }
            }
        }
    }

    private function getCountriesData($countryCodes)
    {
        if (empty($countryCodes)) {
            return [];
        }

        $countries = Country::where(function($query) use ($countryCodes) {
            $query->whereIn('iso', $countryCodes)
                  ->orWhereIn('iso3', $countryCodes);
        })->get();

        $detectedCountries = [];
        foreach ($countries as $country) {
            $detectedCountries[] = [
                'id' => $country->id,
                'name' => $country->name,
                'e_name' => $country->e_name ?? $country->name,
                'iso2' => $country->iso2 ?? '',
                'iso3' => $country->iso3 ?? '',
                'phone_code' => $country->phone_code ?? ''
            ];
        }

        return $detectedCountries;
    }

    private function isDuplicatePoint($newPoint, $existingPoints, $threshold = 0.01)
    {
        foreach ($existingPoints as $existing) {
            if (abs($existing['lat'] - $newPoint['lat']) < $threshold && 
                abs($existing['lng'] - $newPoint['lng']) < $threshold) {
                return true;
            }
        }
        return false;
    }

    private function calculateBounds($polygon)
    {
        $minLat = $maxLat = $polygon[0]['lat'];
        $minLng = $maxLng = $polygon[0]['lng'];

        foreach ($polygon as $point) {
            $minLat = min($minLat, $point['lat']);
            $maxLat = max($maxLat, $point['lat']);
            $minLng = min($minLng, $point['lng']);
            $maxLng = max($maxLng, $point['lng']);
        }

        return [
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLng' => $minLng,
            'maxLng' => $maxLng
        ];
    }

    private function isPointInPolygon($lat, $lng, $polygon)
    {
        $vertices = count($polygon);
        $inside = false;

        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            $intersect = (($yi > $lng) != ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}