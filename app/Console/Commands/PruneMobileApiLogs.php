<?php

namespace App\Console\Commands;

use App\Models\MobileApiLog;
use Illuminate\Console\Command;

class PruneMobileApiLogs extends Command
{
    protected $signature = 'mobile-api-logs:prune';

    protected $description = 'Elimina registros de la app móvil anteriores al período de conservación configurado';

    public function handle(): int
    {
        $cutoff = now('UTC')->subDays(config('mobile_api_logs.retention_days'));
        $deleted = 0;
        do {
            $ids = MobileApiLog::where('received_at', '<', $cutoff)->orderBy('id')->limit(1000)->pluck('id');
            $deleted += MobileApiLog::whereIn('id', $ids)->delete();
        } while ($ids->count() === 1000);
        $this->info("Registros eliminados: {$deleted}");

        return self::SUCCESS;
    }
}
