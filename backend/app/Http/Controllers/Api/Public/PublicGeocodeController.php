<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PublicGeocodeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '' || mb_strlen($q) > 255) {
            return response()->json(['data' => null]);
        }

        $attempts = [];
        if (! preg_match('/rennes|france|سودان/iu', $q)) {
            $attempts[] = $q.', Rennes, France';
        }
        $attempts[] = $q;

        foreach ($attempts as $attempt) {
            try {
                $pending = Http::withHeaders([
                    'User-Agent' => 'ASO-Soudan-Rennes/1.0 (association maps)',
                    'Accept' => 'application/json',
                ])->timeout(8);

                if (app()->environment('local')) {
                    $pending = $pending->withOptions(['verify' => false]);
                }

                $response = $pending->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'q' => $attempt,
                ]);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $hit = $response->json()[0] ?? null;
            if (! is_array($hit) || empty($hit['lat']) || empty($hit['lon'])) {
                continue;
            }

            return response()->json([
                'data' => [
                    'lat' => (float) $hit['lat'],
                    'lon' => (float) $hit['lon'],
                    'label' => (string) ($hit['display_name'] ?? $attempt),
                ],
            ]);
        }

        return response()->json(['data' => null]);
    }
}
