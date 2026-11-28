<?php

namespace App\Services\Recommendation;

use App\Models\Basecamp;
use App\Models\Booking;
use App\Models\Gunung;
use App\Models\Peralatan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    public function getRecommendations(User $user, bool $refresh = false, array $filters = []): array
    {
        $gunungId = isset($filters['gunung_id']) ? (int) $filters['gunung_id'] : null;
        $basecampId = isset($filters['basecamp_id']) ? (int) $filters['basecamp_id'] : null;
        $cacheKey = "recommendations:user:{$user->id}:g" . ($gunungId ?: 0) . ":b" . ($basecampId ?: 0);

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($user, $gunungId, $basecampId) {
            $profile = $this->buildProfile($user);
            $mountains = $this->recommendMountains($user, $profile, $gunungId);
            $equipments = $this->recommendEquipments($user, $profile, $mountains, $gunungId, $basecampId);

            $ai = $this->enhanceWithAi($user, $profile, $mountains, $equipments);
            if ($ai) {
                $mountains = $ai['mountains'];
                $equipments = $ai['equipments'];
            }

            return [
                'profile' => $profile,
                'mountains' => array_values($mountains),
                'equipments' => array_values($equipments),
                'filters' => [
                    'gunung_id' => $gunungId,
                    'basecamp_id' => $basecampId,
                ],
                'generated_at' => now()->toIso8601String(),
                'source' => $ai ? 'hybrid' : 'deterministic',
            ];
        });
    }

    private function buildProfile(User $user): array
    {
        $completedBookings = Booking::with([
            'basecamp.gunung',
            'basecamp.jalurs',
            'detailSewa.peralatan.kategori',
            'members',
        ])
            ->where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed', 'checkin', 'checked_in'])
            ->orderByDesc('tanggal_naik')
            ->get();

        $allBookings = Booking::where('user_id', $user->id)->get();

        $totalHikes = $completedBookings->count();
        $visitedGunungIds = $completedBookings
            ->pluck('basecamp.gunung_id')
            ->filter()
            ->unique()
            ->values();

        $visitedGunungs = $completedBookings
            ->map(fn ($b) => $b->basecamp?->gunung)
            ->filter()
            ->unique('id')
            ->values();

        $maxAltitude = (int) $visitedGunungs->max('ketinggian');
        $avgAltitude = $visitedGunungs->count() > 0 ? (float) $visitedGunungs->avg('ketinggian') : 0.0;

        $rentedCategories = [];
        foreach ($completedBookings as $booking) {
            foreach ($booking->detailSewa as $detail) {
                $category = $detail->peralatan?->kategori?->nama_kategori;
                if ($category) {
                    $rentedCategories[$category] = ($rentedCategories[$category] ?? 0) + (int) $detail->jumlah;
                }
            }
        }
        arsort($rentedCategories);

        $age = $this->resolveAge($user, $completedBookings);
        $computedLevel = $this->experienceLevel($totalHikes, $maxAltitude, $avgAltitude);
        $experienceLevel = in_array($user->experience_level, ['beginner', 'intermediate', 'advanced'], true)
            ? $user->experience_level
            : $computedLevel;

        $cancelled = $allBookings->whereIn('status', ['cancelled', 'canceled'])->count();
        $totalBookings = $allBookings->count();

        $preferredLocations = $visitedGunungs
            ->pluck('lokasi')
            ->filter()
            ->map(fn ($lokasi) => $this->normalizeLocation($lokasi))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(3)
            ->keys()
            ->values()
            ->all();

        $preferensi = is_array($user->preferensi) ? $user->preferensi : [];
        $preferredLocationPref = $this->normalizeLocation($preferensi['lokasi_favorit'] ?? null);
        if ($preferredLocationPref) {
            $preferredLocations = array_values(array_unique(array_merge([$preferredLocationPref], $preferredLocations)));
        }

        $ownedEquipment = collect($preferensi['alat_dimiliki'] ?? [])
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => mb_strtolower(trim($v)))
            ->values()
            ->all();

        $recentGunungIds = $completedBookings
            ->filter(fn ($b) => $b->tanggal_naik && now()->diffInMonths($b->tanggal_naik) <= 6)
            ->pluck('basecamp.gunung_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'name' => $user->name,
            'age' => $age,
            'experience_level' => $experienceLevel,
            'total_hikes' => $totalHikes,
            'total_bookings' => $totalBookings,
            'cancelled_bookings' => $cancelled,
            'visited_gunung_ids' => $visitedGunungIds->all(),
            'recent_gunung_ids' => $recentGunungIds,
            'max_altitude' => $maxAltitude,
            'avg_altitude' => round($avgAltitude),
            'preferred_locations' => array_slice($preferredLocations, 0, 4),
            'rented_categories' => array_keys(array_slice($rentedCategories, 0, 5)),
            'owned_equipment' => $ownedEquipment,
            'budget_sewa' => isset($preferensi['budget_sewa']) && is_numeric($preferensi['budget_sewa'])
                ? (int) $preferensi['budget_sewa']
                : null,
        ];
    }

    private function resolveAge(User $user, $bookings): ?int
    {
        if ($user->tanggal_lahir) {
            try {
                $age = now()->diffInYears($user->tanggal_lahir);
                if ($age >= 10 && $age <= 80) return (int) $age;
            } catch (\Throwable $e) {
            }
        }

        foreach ($bookings as $booking) {
            foreach ($booking->members as $member) {
                if (!$member->tanggal_lahir) continue;
                try {
                    $age = now()->diffInYears($member->tanggal_lahir);
                    if ($age >= 10 && $age <= 80) return (int) $age;
                } catch (\Throwable $e) {
                }
            }
        }
        return null;
    }

    private function experienceLevel(int $totalHikes, int $maxAltitude, float $avgAltitude): string
    {
        $score = 0;
        $score += min($totalHikes, 10) * 10;
        $score += min($maxAltitude / 100, 40);
        $score += min($avgAltitude / 150, 20);

        if ($score >= 90) return 'advanced';
        if ($score >= 40) return 'intermediate';
        return 'beginner';
    }

    private function normalizeLocation(?string $lokasi): ?string
    {
        if (!$lokasi) return null;
        $parts = array_map('trim', explode(',', $lokasi));
        $last = end($parts);
        return $last ?: null;
    }

    private function recommendMountains(User $user, array $profile, ?int $gunungId = null): array
    {
        $gunungs = Gunung::with(['basecamps.jalurs', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('status', true)
            ->get();

        $visitedIds = collect($profile['visited_gunung_ids'] ?? []);
        $recentIds = collect($profile['recent_gunung_ids'] ?? []);
        $preferredLocations = collect($profile['preferred_locations'] ?? [])
            ->map(fn ($v) => mb_strtolower($v))
            ->all();

        $maxPopularity = max(1, (int) $gunungs->max('reviews_count'));

        $results = [];
        foreach ($gunungs as $gunung) {
            $basecamps = $gunung->basecamps ?? collect();
            $jalurs = $basecamps->flatMap(fn ($b) => $b->jalurs ?? collect());
            $openJalur = $jalurs->firstWhere('status', 'buka') ?? $jalurs->first();
            $estimasi = $openJalur?->estimasi_waktu;
            $estimasiHours = is_numeric($estimasi) ? ((float) $estimasi / 60) : null;
            $ketinggian = (int) ($gunung->ketinggian ?? 0);

            $difficultyMap = ['mudah' => 'easy', 'sedang' => 'medium', 'sulit' => 'hard'];
            $difficulty = $difficultyMap[$gunung->tingkat_kesulitan ?? ''] ?? $this->inferDifficulty($ketinggian, $estimasiHours);
            $experienceFit = $this->experienceFit($profile['experience_level'], $difficulty, $ketinggian);
            $ratingScore = ((float) ($gunung->reviews_avg_rating ?? 0)) / 5;
            $popularity = ((int) ($gunung->reviews_count ?? 0)) / $maxPopularity;
            $novelty = $visitedIds->contains($gunung->id) ? 0.2 : 1.0;
            if ($recentIds->contains($gunung->id)) {
                $novelty = 0.05;
            }

            $location = mb_strtolower($this->normalizeLocation($gunung->lokasi) ?? '');
            $locationFit = $location && in_array($location, $preferredLocations, true) ? 1.0 : 0.5;
            if (empty($preferredLocations)) {
                $locationFit = 0.6;
            }

            $score = (0.35 * $experienceFit)
                + (0.20 * $ratingScore)
                + (0.15 * $popularity)
                + (0.15 * $novelty)
                + (0.15 * $locationFit);

            $score = round(max(0, min(1, $score)), 4);

            $reasons = $this->mountainReasons($profile, $gunung, $difficulty, $experienceFit, $novelty, $locationFit, $ratingScore);

            $primaryBasecamp = $basecamps->sortByDesc(fn ($b) => $b->kuota ?? 0)->first();

            $results[] = [
                'id' => $gunung->id,
                'nama' => $gunung->nama,
                'lokasi' => $gunung->lokasi,
                'ketinggian' => $ketinggian,
                'status' => $gunung->status,
                'foto_utama' => $gunung->foto_utama,
                'rating' => round((float) ($gunung->reviews_avg_rating ?? 0), 1),
                'reviews_count' => (int) ($gunung->reviews_count ?? 0),
                'difficulty' => $difficulty,
                'estimasi_jam' => $estimasiHours ? round($estimasiHours, 1) : null,
                'score' => $score,
                'reasons' => $reasons,
                'reason' => implode(' ', $reasons),
                'basecamp' => $primaryBasecamp ? [
                    'id' => $primaryBasecamp->id,
                    'nama' => $primaryBasecamp->nama,
                    'harga_tiket' => $primaryBasecamp->harga_tiket,
                ] : null,
            ];
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, 6);
    }

    private function inferDifficulty(int $ketinggian, ?float $estimasiHours): string
    {
        $score = 0;
        if ($ketinggian >= 3400) $score += 2;
        elseif ($ketinggian >= 2800) $score += 1.5;
        elseif ($ketinggian >= 2200) $score += 1;
        else $score += 0.5;

        if ($estimasiHours !== null) {
            if ($estimasiHours >= 10) $score += 1.5;
            elseif ($estimasiHours >= 7) $score += 1;
            elseif ($estimasiHours >= 4) $score += 0.5;
        }

        if ($score >= 3) return 'hard';
        if ($score >= 1.8) return 'medium';
        return 'easy';
    }

    private function experienceFit(string $level, string $difficulty, int $ketinggian): float
    {
        $matrix = [
            'beginner' => ['easy' => 1.0, 'medium' => 0.55, 'hard' => 0.2],
            'intermediate' => ['easy' => 0.75, 'medium' => 1.0, 'hard' => 0.65],
            'advanced' => ['easy' => 0.55, 'medium' => 0.85, 'hard' => 1.0],
        ];

        $fit = $matrix[$level][$difficulty] ?? 0.5;

        if ($level === 'beginner' && $ketinggian > 3000) $fit -= 0.2;
        if ($level === 'advanced' && $ketinggian < 2000) $fit -= 0.1;

        return max(0, min(1, $fit));
    }

    private function mountainReasons(array $profile, Gunung $gunung, string $difficulty, float $experienceFit, float $novelty, float $locationFit, float $ratingScore): array
    {
        $reasons = [];
        $levelLabel = [
            'beginner' => 'pemula',
            'intermediate' => 'menengah',
            'advanced' => 'berpengalaman',
        ][$profile['experience_level']] ?? 'pendaki';

        $difficultyLabel = [
            'easy' => 'ramah untuk pemula',
            'medium' => 'tingkat menengah',
            'hard' => 'menantang',
        ][$difficulty] ?? 'sesuai';

        if ($experienceFit >= 0.8) {
            $reasons[] = "Cocok untuk pendaki {$levelLabel} dengan medan {$difficultyLabel}.";
        } elseif ($experienceFit >= 0.55) {
            $reasons[] = "Bisa jadi pilihan berikutnya untuk level {$levelLabel}, dengan persiapan yang matang.";
        }

        if ($novelty >= 1.0) {
            $reasons[] = "Kamu belum pernah mendaki gunung ini, jadi cocok untuk pengalaman baru.";
        } elseif ($novelty <= 0.1) {
            $reasons[] = "Kamu baru saja mendaki gunung ini, jadi rekomendasinya lebih rendah untuk variasi.";
        }

        if ($locationFit >= 0.9) {
            $reasons[] = "Lokasinya mirip dengan area pendakian yang sering kamu pilih.";
        }

        if ($ratingScore >= 0.8 && ($gunung->reviews_count ?? 0) > 0) {
            $reasons[] = "Rating pendaki lain bagus (" . number_format((float) $gunung->reviews_avg_rating, 1) . "/5).";
        }

        if (empty($reasons)) {
            $reasons[] = "Gunung ini populer dan bisa dipertimbangkan untuk pendakian berikutnya.";
        }

        return array_slice($reasons, 0, 2);
    }

    private function recommendEquipments(User $user, array $profile, array $mountains, ?int $gunungId = null, ?int $basecampId = null): array
    {
        $targetGunungIds = collect($mountains)->pluck('id')->filter()->all();
        if ($gunungId) {
            $targetGunungIds = array_values(array_unique(array_merge([$gunungId], $targetGunungIds)));
        }

        $basecampIds = Basecamp::whereIn('gunung_id', $targetGunungIds)->pluck('id')->all();
        if ($basecampId) {
            $basecampIds = [$basecampId];
        }

        $query = Peralatan::with(['kategori', 'basecamp.gunung'])
            ->where('stok', '>', 0);

        if (!empty($basecampIds)) {
            $query->whereIn('basecamp_id', $basecampIds);
        }

        $peralatans = $query->get();
        if ($peralatans->isEmpty() && !$basecampId) {
            $peralatans = Peralatan::with(['kategori', 'basecamp.gunung'])
                ->where('stok', '>', 0)
                ->limit(30)
                ->get();
        }

        $rentedCategories = collect($profile['rented_categories'] ?? [])->map(fn ($v) => mb_strtolower($v))->all();
        $ownedEquipment = collect($profile['owned_equipment'] ?? [])->map(fn ($v) => mb_strtolower($v))->all();
        $budgetSewa = isset($profile['budget_sewa']) && is_numeric($profile['budget_sewa']) ? (int) $profile['budget_sewa'] : null;
        $level = $profile['experience_level'] ?? 'beginner';
        $maxAltitude = (int) ($profile['max_altitude'] ?? 0);

        $essentialByLevel = [
            'beginner' => ['tenda', 'sleeping bag', 'matras', 'jaket', 'headlamp', 'carrier', 'sepatu'],
            'intermediate' => ['tenda', 'sleeping bag', 'matras', 'jaket', 'headlamp', 'trekking pole', 'kompor', 'carrier'],
            'advanced' => ['tenda', 'sleeping bag', 'matras', 'jaket', 'headlamp', 'trekking pole', 'kompor', 'rain cover', 'carrier'],
        ];
        $essentials = $essentialByLevel[$level] ?? $essentialByLevel['beginner'];
        if ($maxAltitude >= 3000 || collect($mountains)->contains(fn ($m) => ($m['ketinggian'] ?? 0) >= 3000)) {
            $essentials = array_values(array_unique(array_merge($essentials, ['jaket', 'sleeping bag', 'sarung tangan'])));
        }

        $results = [];
        foreach ($peralatans as $alat) {
            $category = mb_strtolower($alat->kategori?->nama_kategori ?? '');
            $name = mb_strtolower($alat->nama_alat ?? '');
            $haystack = $category . ' ' . $name;

            $essentialScore = 0;
            foreach ($essentials as $essential) {
                if (str_contains($haystack, $essential)) {
                    $essentialScore = 1.0;
                    break;
                }
            }
            if ($essentialScore === 0) {
                $essentialScore = 0.35;
            }

            $notRentedBonus = 0.7;
            foreach ($rentedCategories as $rented) {
                if ($rented && str_contains($haystack, $rented)) {
                    $notRentedBonus = 0.3;
                    break;
                }
            }

            $ownedPenalty = 0;
            foreach ($ownedEquipment as $owned) {
                if ($owned && str_contains($haystack, $owned)) {
                    $ownedPenalty = 0.35;
                    break;
                }
            }

            $stockScore = min(1, ((int) $alat->stok) / 10);
            $price = (float) ($alat->harga_sewa ?? 0);
            if ($budgetSewa && $budgetSewa > 0) {
                $priceScore = $price <= $budgetSewa ? 1.0 : max(0, 1 - (($price - $budgetSewa) / max($budgetSewa, 1)));
            } else {
                $priceScore = $price > 0 ? max(0, 1 - min(1, $price / 150000)) : 0.5;
            }

            $score = (0.40 * $essentialScore)
                + (0.20 * $notRentedBonus)
                + (0.15 * $stockScore)
                + (0.15 * $priceScore)
                + (0.10 * (1 - $ownedPenalty));

            $reasons = [];
            if ($essentialScore >= 1.0) {
                $reasons[] = "Peralatan ini penting untuk pendakian level {$this->levelLabel($level)}.";
            }
            if ($ownedPenalty > 0) {
                $reasons[] = "Kamu mungkin sudah punya alat serupa, jadi prioritasnya lebih rendah.";
            } elseif ($notRentedBonus >= 0.7) {
                $reasons[] = "Kamu belum sering menyewa kategori ini, jadi layak dipertimbangkan.";
            }
            if ($budgetSewa && $price > 0 && $price <= $budgetSewa) {
                $reasons[] = "Masih sesuai dengan budget sewa yang kamu atur.";
            }
            if ($stockScore >= 0.5) {
                $reasons[] = "Stok tersedia di basecamp terkait.";
            }
            if (empty($reasons)) {
                $reasons[] = "Bisa membantu kenyamanan dan keamanan pendakianmu.";
            }

            $results[] = [
                'id' => $alat->id,
                'nama_alat' => $alat->nama_alat,
                'kategori' => $alat->kategori?->nama_kategori,
                'harga_sewa' => (int) ($alat->harga_sewa ?? 0),
                'stok' => (int) ($alat->stok ?? 0),
                'foto' => $alat->foto,
                'deskripsi' => $alat->deskripsi,
                'basecamp' => $alat->basecamp ? [
                    'id' => $alat->basecamp->id,
                    'nama' => $alat->basecamp->nama,
                    'gunung_id' => $alat->basecamp->gunung_id,
                    'gunung_nama' => $alat->basecamp->gunung?->nama,
                ] : null,
                'score' => round(max(0, min(1, $score)), 4),
                'reasons' => array_slice($reasons, 0, 2),
                'reason' => implode(' ', array_slice($reasons, 0, 2)),
            ];
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
        $results = $this->uniqueByCategory($results, 8);
        return array_slice($results, 0, 8);
    }

    private function uniqueByCategory(array $items, int $limit): array
    {
        $picked = [];
        $seen = [];
        foreach ($items as $item) {
            $key = mb_strtolower($item['kategori'] ?? $item['nama_alat'] ?? 'item');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $picked[] = $item;
            if (count($picked) >= $limit) break;
        }
        if (count($picked) < $limit) {
            foreach ($items as $item) {
                if (in_array($item['id'], array_column($picked, 'id'), true)) continue;
                $picked[] = $item;
                if (count($picked) >= $limit) break;
            }
        }
        return $picked;
    }

    private function levelLabel(string $level): string
    {
        return [
            'beginner' => 'pemula',
            'intermediate' => 'menengah',
            'advanced' => 'berpengalaman',
        ][$level] ?? 'pendaki';
    }

    private function enhanceWithAi(User $user, array $profile, array $mountains, array $equipments): ?array
    {
        $config = config('services.chatbot');
        if (!($config['enabled'] ?? true) || !($config['api_key'] ?? null)) {
            return null;
        }

        $candidateMountains = array_map(fn ($m) => [
            'id' => $m['id'],
            'nama' => $m['nama'],
            'lokasi' => $m['lokasi'],
            'ketinggian' => $m['ketinggian'],
            'difficulty' => $m['difficulty'],
            'rating' => $m['rating'],
            'reviews_count' => $m['reviews_count'],
            'score' => $m['score'],
        ], array_slice($mountains, 0, 6));

        $candidateEquipments = array_map(fn ($e) => [
            'id' => $e['id'],
            'nama_alat' => $e['nama_alat'],
            'kategori' => $e['kategori'],
            'harga_sewa' => $e['harga_sewa'],
            'stok' => $e['stok'],
            'gunung_nama' => $e['basecamp']['gunung_nama'] ?? null,
            'score' => $e['score'],
        ], array_slice($equipments, 0, 8));

        $prompt = implode("\n", [
            'Kamu adalah asisten rekomendasi pendakian SummitGo.',
            'Tugas: pilih dan jelaskan rekomendasi terbaik dari kandidat yang diberikan.',
            'Aturan:',
            '- Jangan mengarang data. Gunakan hanya kandidat yang diberikan.',
            '- Kembalikan JSON valid saja, tanpa markdown, tanpa teks tambahan.',
            '- Format: {"mountains":[{"id":1,"reason":"..."}],"equipments":[{"id":1,"reason":"..."}]}',
            '- mountains maksimal 4 item, equipments maksimal 6 item.',
            '- reason singkat, jelas, personal, Bahasa Indonesia, 1-2 kalimat.',
            '',
            'Profil user (agregat, tanpa PII): ' . json_encode([
                'experience_level' => $profile['experience_level'],
                'total_hikes' => $profile['total_hikes'],
                'max_altitude' => $profile['max_altitude'],
                'preferred_locations' => $profile['preferred_locations'],
                'rented_categories' => $profile['rented_categories'],
                'age' => $profile['age'],
            ], JSON_UNESCAPED_UNICODE),
            '',
            'Kandidat gunung: ' . json_encode($candidateMountains, JSON_UNESCAPED_UNICODE),
            '',
            'Kandidat peralatan: ' . json_encode($candidateEquipments, JSON_UNESCAPED_UNICODE),
        ]);

        try {
            $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
            $model = trim((string) ($config['model'] ?? 'gemini-2.5-flash'));
            $apiKey = (string) ($config['api_key'] ?? '');
            $endpoint = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout((int) ($config['timeout'] ?? 20))
                ->acceptJson()
                ->post($endpoint, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $prompt]],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 800,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('Recommendation AI request failed', ['status' => $response->status(), 'user_id' => $user->id]);
                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            $text = is_string($text) ? trim($text) : '';
            if ($text === '') return null;

            $text = preg_replace('/^```json\s*|\s*```$/i', '', $text);
            $text = trim((string) $text);
            $decoded = json_decode($text, true);
            if (!is_array($decoded)) return null;

            $mountains = $this->applyAiReasons($mountains, $decoded['mountains'] ?? [], 4);
            $equipments = $this->applyAiReasons($equipments, $decoded['equipments'] ?? [], 6);

            return ['mountains' => $mountains, 'equipments' => $equipments];
        } catch (\Throwable $e) {
            Log::warning('Recommendation AI exception', ['user_id' => $user->id, 'message' => $e->getMessage()]);
            return null;
        }
    }

    private function applyAiReasons(array $items, $aiItems, int $limit): array
    {
        if (!is_array($aiItems)) return array_slice($items, 0, $limit);

        $byId = [];
        foreach ($items as $item) {
            $byId[(int) $item['id']] = $item;
        }

        $ordered = [];
        foreach ($aiItems as $aiItem) {
            if (!is_array($aiItem)) continue;
            $id = (int) ($aiItem['id'] ?? 0);
            if (!$id || !isset($byId[$id])) continue;
            $item = $byId[$id];
            $reason = trim((string) ($aiItem['reason'] ?? ''));
            if ($reason !== '') {
                $item['reason'] = $reason;
                $item['reasons'] = [$reason];
                $item['ai_reason'] = true;
            }
            $ordered[] = $item;
            unset($byId[$id]);
            if (count($ordered) >= $limit) break;
        }

        if (count($ordered) < $limit) {
            foreach ($items as $item) {
                if (count($ordered) >= $limit) break;
                if (in_array((int) $item['id'], array_column($ordered, 'id'), true)) continue;
                $ordered[] = $item;
            }
        }

        return array_slice($ordered, 0, $limit);
    }
}
