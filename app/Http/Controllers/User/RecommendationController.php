<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RecommendationEvent;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationService $recommendationService)
    {
    }

    public function index(Request $request)
    {
        $refresh = filter_var($request->query('refresh', false), FILTER_VALIDATE_BOOLEAN);
        $filters = [
            'gunung_id' => $request->query('gunung_id'),
            'basecamp_id' => $request->query('basecamp_id'),
        ];

        $data = $this->recommendationService->getRecommendations($request->user(), $refresh, $filters);

        try {
            RecommendationEvent::create([
                'user_id' => $request->user()->id,
                'event_type' => 'impression',
                'target_type' => 'recommendation',
                'target_id' => null,
                'metadata' => [
                    'filters' => $filters,
                    'source' => $data['source'] ?? null,
                    'mountain_ids' => collect($data['mountains'] ?? [])->pluck('id')->take(10)->values()->all(),
                    'equipment_ids' => collect($data['equipments'] ?? [])->pluck('id')->take(10)->values()->all(),
                ],
            ]);
        } catch (\Throwable $e) {
        }

        return response()->json([
            'message' => 'Rekomendasi berhasil dibuat',
            'data' => $data,
        ]);
    }

    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|in:click,booking,dismiss',
            'target_type' => 'nullable|string|in:mountain,equipment,recommendation',
            'target_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        $event = RecommendationEvent::create([
            'user_id' => $request->user()->id,
            'event_type' => $validated['event_type'],
            'target_type' => $validated['target_type'] ?? null,
            'target_id' => $validated['target_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'message' => 'Event rekomendasi dicatat',
            'data' => ['id' => $event->id],
        ]);
    }
}
