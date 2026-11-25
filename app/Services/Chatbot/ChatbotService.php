<?php

namespace App\Services\Chatbot;

use App\Models\Booking;
use App\Models\ChatbotMessage;
use App\Models\Gunung;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    public function reply(User $user, string $message): ChatbotMessage
    {
        $config = config('services.chatbot');

        if (!($config['enabled'] ?? true)) {
            return $this->storeAssistantMessage($user, 'Layanan chatbot sedang tidak aktif. Silakan coba lagi nanti.', [
                'error' => 'disabled',
            ]);
        }

        $apiKey = $config['api_key'] ?? null;
        if (!$apiKey) {
            return $this->storeAssistantMessage($user, 'Konfigurasi AI belum lengkap. Silakan hubungi admin aplikasi.', [
                'error' => 'missing_api_key',
            ]);
        }

        $history = $this->recentMessages($user, (int) ($config['max_messages'] ?? 20));
        $systemPrompt = $this->buildSystemPrompt($user);
        $contents = $this->buildContents($history);

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 20))
                ->acceptJson()
                ->post($this->endpoint($config), [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 900,
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('Chatbot Gemini request failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'user_id' => $user->id,
                ]);

                return $this->storeAssistantMessage($user, 'Maaf, asisten sedang mengalami gangguan. Silakan coba beberapa saat lagi.', [
                    'error' => 'provider_error',
                    'status' => $response->status(),
                ]);
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            $text = is_string($text) ? trim($text) : '';

            if ($text === '') {
                $blockReason = data_get($response->json(), 'promptFeedback.blockReason')
                    ?? data_get($response->json(), 'candidates.0.finishReason');

                Log::warning('Chatbot Gemini returned empty response', [
                    'user_id' => $user->id,
                    'reason' => $blockReason,
                    'body' => $response->json(),
                ]);

                return $this->storeAssistantMessage($user, 'Maaf, saya belum bisa memproses permintaan itu. Coba tanyakan dengan kalimat lain.', [
                    'error' => 'empty_response',
                    'reason' => $blockReason,
                ]);
            }

            return $this->storeAssistantMessage($user, $text, [
                'model' => $config['model'] ?? null,
                'provider' => $config['provider'] ?? 'gemini',
            ]);
        } catch (\Throwable $e) {
            Log::error('Chatbot Gemini exception', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return $this->storeAssistantMessage($user, 'Maaf, terjadi kendala saat menghubungi asisten. Silakan coba lagi.', [
                'error' => 'exception',
            ]);
        }
    }

    public function storeUserMessage(User $user, string $content): ChatbotMessage
    {
        return ChatbotMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $content,
        ]);
    }

    public function storeAssistantMessage(User $user, string $content, array $metadata = []): ChatbotMessage
    {
        return ChatbotMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $content,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function recentMessages(User $user, int $limit): array
    {
        $limit = max(1, min($limit, 30));

        return ChatbotMessage::where('user_id', $user->id)
            ->latest('id')
            ->limit($limit)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->all();
    }

    private function buildContents(array $history): array
    {
        $contents = [];

        foreach ($history as $item) {
            $role = $item->role === 'assistant' ? 'model' : 'user';
            $text = trim((string) $item->content);
            if ($text === '') {
                continue;
            }
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]],
            ];
        }

        if (empty($contents)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => 'Halo']],
            ];
        }

        return $contents;
    }

    private function buildSystemPrompt(User $user): string
    {
        $userName = $user->name ?: 'Pengguna';
        $bookingsContext = $this->bookingsContext($user);
        $mountainsContext = $this->mountainsContext();

        return implode("\n", [
            'Kamu adalah "SummitGo Assistant", teman pendakian digital dari aplikasi SummitGo.',
            'Kamu membantu pengguna dengan hangat, ramah, dan tetap profesional.',
            '',
            'Kepribadian & gaya bicara:',
            "- Sapa pengguna secara natural. Gunakan nama pengguna ({$userName}) sesekali jika terasa pas, jangan berlebihan.",
            '- Tulis seperti asisten yang suportif dan paham dunia pendakian, bukan robot kaku.',
            '- Empatik saat pengguna bingung atau mengalami kendala, tapi tetap jelas dan solutif.',
            '- Boleh pakai emoji secukupnya (maksimal 1-2 per respons) hanya jika cocok; jangan memaksa.',
            '',
            'Format jawaban:',
            '- Gunakan Bahasa Indonesia yang natural dan mudah dibaca.',
            '- Mulai dengan jawaban inti atau kalimat pembuka singkat yang relevan.',
            '- Pecah jawaban jadi paragraf pendek.',
            '- Gunakan bullet list (- ...) untuk langkah, tips, atau daftar.',
            '- Gunakan **tebal** untuk info penting seperti status booking, kode booking, tanggal, nama gunung/basecamp, atau poin penting.',
            '- Jika membantu, akhiri dengan 1 pertanyaan tindak lanjut singkat.',
            '- Jangan terlalu panjang; prioritaskan jawaban yang praktis.',
            '',
            'Tugas utama:',
            '1) Panduan pendakian: persiapan, perlengkapan, keselamatan, etika, cuaca umum, tips jalur, dan info umum gunung.',
            '2) Bantuan booking/CS: alur registrasi, pembayaran, status booking, e-tiket, reschedule/cancel, dan kendala aplikasi.',
            '',
            'Aturan penting:',
            '- Gunakan data konteks yang diberikan. Jangan mengarang status booking, pembayaran, atau data pribadi.',
            '- Jika data booking tidak ada atau tidak cukup, katakan jujur dan arahkan pengguna cek menu **Transaksi/Riwayat** atau hubungi admin.',
            '- Untuk keselamatan pendakian, utamakan saran aman. Jika kondisi berisiko, sarankan cek info resmi basecamp/BPBD/cuaca terkini dan pertimbangkan menunda pendakian.',
            '- Jangan memberikan instruksi berbahaya, ilegal, atau meminta informasi sensitif seperti password, OTP, atau token.',
            '- Jika pertanyaan di luar topik SummitGo/pendakian/booking, tolak dengan sopan dan arahkan kembali ke topik yang bisa dibantu.',
            '',
            'Informasi pengguna saat ini:',
            "- Nama: {$userName}",
            "- Email: {$user->email}",
            '',
            'Konteks booking terbaru pengguna:',
            $bookingsContext,
            '',
            'Konteks gunung yang tersedia di sistem (ringkas):',
            $mountainsContext,
        ]);
    }

    private function bookingsContext(User $user): string
    {
        $bookings = Booking::with(['basecamp.gunung'])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        if ($bookings->isEmpty()) {
            return '- Belum ada booking untuk pengguna ini.';
        }

        return $bookings->map(function (Booking $booking) {
            $gunung = $booking->basecamp?->gunung?->nama ?? '-';
            $basecamp = $booking->basecamp?->nama ?? '-';
            $tanggal = $booking->tanggal_naik ? (string) $booking->tanggal_naik : '-';
            $status = $booking->status ?? '-';
            $kode = $booking->kode_booking ?? ('#' . $booking->id);
            $jumlah = $booking->jumlah_pendaki ?? '-';

            return "- {$kode} | Gunung: {$gunung} | Basecamp: {$basecamp} | Tanggal naik: {$tanggal} | Jumlah: {$jumlah} | Status: {$status}";
        })->implode("\n");
    }

    private function mountainsContext(): string
    {
        $mountains = Gunung::query()
            ->select(['id', 'nama', 'lokasi', 'status', 'ketinggian'])
            ->latest('id')
            ->limit(10)
            ->get();

        if ($mountains->isEmpty()) {
            return '- Belum ada data gunung.';
        }

        return $mountains->map(function (Gunung $gunung) {
            $nama = $gunung->nama ?? '-';
            $lokasi = $gunung->lokasi ?? '-';
            $status = $gunung->status ?? '-';
            $ketinggian = $gunung->ketinggian ? $gunung->ketinggian . ' mdpl' : '-';
            return "- {$nama} | Lokasi: {$lokasi} | Ketinggian: {$ketinggian} | Status: {$status}";
        })->implode("\n");
    }

    private function endpoint(array $config): string
    {
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = trim((string) ($config['model'] ?? 'gemini-2.5-flash'));
        $apiKey = (string) ($config['api_key'] ?? '');

        return "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";
    }
}
