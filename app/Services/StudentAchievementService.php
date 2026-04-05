<?php

namespace App\Services;

use App\Models\Document;
use App\Models\StudentAchievement;
use App\Models\StudentChecklist;
use App\Models\StudentFeedback;
use App\Models\StudentMaterialRead;

class StudentAchievementService
{
    /**
     * Check all badge conditions for the given student and award any newly earned badges.
     * Returns array of newly earned badge codes.
     */
    public function checkAndAward(string $studentId, string $companyId): array
    {
        $badges = config('student_achievements.badges', []);
        if (empty($badges)) {
            return [];
        }

        $existing = StudentAchievement::where('student_id', $studentId)
            ->pluck('achievement_code')
            ->flip()
            ->all();

        $newlyEarned = [];

        foreach ($badges as $badge) {
            $code = $badge['code'];
            if (isset($existing[$code])) {
                continue;
            }

            if ($this->evaluateCondition($badge['condition'], $studentId, $companyId)) {
                StudentAchievement::create([
                    'student_id'       => $studentId,
                    'achievement_code' => $code,
                    'earned_at'        => now(),
                ]);
                $newlyEarned[] = $code;
            }
        }

        return $newlyEarned;
    }

    /**
     * Evaluate a single badge condition against current student data.
     */
    private function evaluateCondition(array $condition, string $studentId, string $companyId): bool
    {
        $type      = $condition['type'] ?? '';
        $threshold = (int) ($condition['threshold'] ?? 1);

        switch ($type) {
            case 'first_login':
                // Awarded on first call — student exists, so always true
                return true;

            case 'profile_complete':
                // Consider profile complete if user record has name + email set (always true for registered users)
                return true;

            case 'docs_uploaded':
                $count = Document::where('student_id', $studentId)
                    ->where('status', '!=', 'rejected')
                    ->count();
                return $count >= $threshold;

            case 'checklist_done':
                $count = StudentChecklist::where('student_id', $studentId)
                    ->where('is_done', true)
                    ->count();
                return $count >= $threshold;

            case 'feedback_given':
                $count = StudentFeedback::where('student_id', $studentId)->count();
                return $count >= $threshold;

            case 'materials_read':
                $count = StudentMaterialRead::where('student_id', $studentId)->count();
                return $count >= $threshold;

            default:
                return false;
        }
    }

    /**
     * Get all earned badges for a student with full badge metadata.
     */
    public function getEarnedBadges(string $studentId): array
    {
        $earned = StudentAchievement::where('student_id', $studentId)
            ->orderBy('earned_at')
            ->pluck('earned_at', 'achievement_code')
            ->all();

        $badges    = config('student_achievements.badges', []);
        $result    = [];

        foreach ($badges as $badge) {
            if (isset($earned[$badge['code']])) {
                $badge['earned_at'] = $earned[$badge['code']];
                $result[]           = $badge;
            }
        }

        return $result;
    }

    /**
     * Total points earned by student.
     */
    public function totalPoints(string $studentId): int
    {
        $earned = StudentAchievement::where('student_id', $studentId)
            ->pluck('achievement_code')
            ->all();

        $badges = collect(config('student_achievements.badges', []))->keyBy('code');
        $points = 0;

        foreach ($earned as $code) {
            $points += (int) ($badges[$code]['points'] ?? 0);
        }

        return $points;
    }
}
