<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;

class CleanupExpiredSoftDeletes extends Command
{
    protected $signature = 'app:cleanup-expired-soft-deletes';

    protected $description = 'Permanently delete soft-deleted records older than 30 days.';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);

        foreach ([Post::class, Category::class, Banner::class] as $modelClass) {
            $modelClass::onlyTrashed()
                ->where('deleted_at', '<=', $cutoff)
                ->forceDelete();
        }

        User::onlyTrashed()
            ->where('role', User::ROLE_ADMIN)
            ->where('deleted_at', '<=', $cutoff)
            ->forceDelete();

        return self::SUCCESS;
    }
}
