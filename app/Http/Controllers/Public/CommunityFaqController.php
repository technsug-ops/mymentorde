<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunityFaqController extends Controller
{
    public function index(Request $request): View
    {
        $config = config('community_faq', ['topics' => []]);
        $topics = $config['topics'] ?? [];

        $totalQuestions = 0;
        foreach ($topics as $t) {
            $totalQuestions += count($t['questions'] ?? []);
        }

        return view('public.community-faq', [
            'topics' => $topics,
            'totalQuestions' => $totalQuestions,
            'topicCount' => count($topics),
        ]);
    }

    public function topic(Request $request, string $topic): View
    {
        $config = config('community_faq', ['topics' => []]);
        $topics = $config['topics'] ?? [];

        if (!isset($topics[$topic])) {
            abort(404);
        }

        return view('public.community-faq-topic', [
            'topicKey'  => $topic,
            'topicData' => $topics[$topic],
            'allTopics' => $topics,
        ]);
    }
}
