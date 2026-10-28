<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneExpiredNews extends Command
{
    protected $signature = 'news:prune-expired';
    protected $description = 'Delete news older than seven days and its image files';

    public function handle(): int
    {
        Post::whereNotNull('published_at')
            ->where('published_at', '<', now()->subDays(7))
            ->chunkById(100, function ($posts): void {
                foreach ($posts as $post) {
                    Storage::disk('public')->delete($post->images ?? array_filter([$post->featured_image]));
                    $post->delete();
                }
            });

        $this->info('Expired news removed.');
        return self::SUCCESS;
    }
}
