<?php

namespace App\Console\Commands;

use App\Models\TrackingSession;
use Illuminate\Console\Command;

class PruneTrackingData extends Command
{
    protected $signature = 'tracking:prune {--days=30 : Hapus sesi yang selesai lebih lama dari jumlah hari ini}';

    protected $description = 'Hapus data tracking GPS yang sudah selesai dan melewati masa retensi';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $total = 0;

        TrackingSession::where('status', 'ended')
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', $cutoff)
            ->chunkById(100, function ($sessions) use (&$total) {
                foreach ($sessions as $session) {
                    // Titik tracking ikut terhapus karena cascadeOnDelete.
                    $session->delete();
                    $total++;
                }
            });

        $this->info("Selesai. {$total} sesi tracking lama dihapus (retensi {$days} hari).");

        return self::SUCCESS;
    }
}
