<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneExpiredNews extends Command
{
    protected $signature = 'news:prune-expired';

    protected $description = 'Retired command; news is no longer automatically expired or deleted';

    public function handle(): int
    {
        $this->info('Automatic news expiration is disabled. No news or images were deleted.');

        return self::SUCCESS;
    }
}
