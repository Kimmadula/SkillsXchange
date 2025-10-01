<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    // Loaded at runtime from resources/data/cebu_barangays.json
    private array $cebuData = [];

    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100']
        ]);

        $query = strtolower($validated['q'] ?? '');

        // Ensure data is loaded
        if (empty($this->cebuData)) {
            $this->cebuData = $this->loadCebuData();
        }

        $matches = [];

        // 1) Match barangays: "Barangay, City, Cebu, Philippines"
        foreach ($this->cebuData as $city => $barangays) {
            foreach ($barangays as $brgy) {
                $full = $brgy . ', ' . $city . ', Cebu, Philippines';
                if ($query === '' || str_contains(strtolower($full), $query)) {
                    $matches[] = $full;
                }
            }
        }

        // 2) Also match just the city names: "City, Cebu, Philippines"
        foreach (array_keys($this->cebuData) as $city) {
            $cityFull = $city . ', Cebu, Philippines';
            if ($query === '' || str_contains(strtolower($cityFull), $query)) {
                $matches[] = $cityFull;
            }
        }

        // Unique and limit results
        $matches = array_values(array_unique($matches));

        return response()->json([
            'suggestions' => array_slice($matches, 0, 15)
        ]);
    }

    private function loadCebuData(): array
    {
        $path = resource_path('data/cebu_barangays.json');
        if (!file_exists($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }
}
