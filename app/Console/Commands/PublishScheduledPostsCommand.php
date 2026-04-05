<?php

namespace App\Console\Commands;

use App\Models\Marketing\SocialMediaPost;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    protected $signature   = 'social:publish-scheduled';
    protected $description = 'Zamanı gelen sosyal medya gönderilerini yayına alır';

    public function handle(): void
    {
        $posts = SocialMediaPost::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($posts as $post) {
            $post->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);
            $count++;
        }

        $this->info("$count gönderi yayına alındı.");
    }
}
