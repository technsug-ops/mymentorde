<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    public function runCriticalCheck(Request $request)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $limit = (int) ($data['limit'] ?? 100);

        $exitCode = Artisan::call('ops:critical-check', ['--limit' => $limit]);
        $output = trim((string) Artisan::output());
        $lines = preg_split('/\r\n|\r|\n/', $output) ?: [];
        $tail = array_slice($lines, -20);

        return response()->json([
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'limit' => $limit,
            'output' => $tail,
        ], $exitCode === 0 ? 200 : 500);
    }

    public function index()
    {
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();

        $jobsQueued = Schema::hasTable('jobs')
            ? (int) DB::table('jobs')->count()
            : 0;

        $failedJobs = Schema::hasTable('failed_jobs')
            ? (int) DB::table('failed_jobs')->count()
            : 0;

        $notificationQueued = (int) NotificationDispatch::query()->where('status', 'queued')->count();
        $notificationFailed = (int) NotificationDispatch::query()->where('status', 'failed')->count();
        $notificationSent24h = (int) NotificationDispatch::query()
            ->where('status', 'sent')
            ->where('sent_at', '>=', $now->copy()->subHours(24))
            ->count();
        $oldestQueuedAt = NotificationDispatch::query()
            ->where('status', 'queued')
            ->orderBy('queued_at')
            ->value('queued_at');
        $oldestQueueDelayMinutes = $oldestQueuedAt
            ? Carbon::parse($oldestQueuedAt)->diffInMinutes($now)
            : 0;
        $lastDispatchActivityAt = NotificationDispatch::query()
            ->whereNotNull('sent_at')
            ->orWhereNotNull('failed_at')
            ->orderByDesc(DB::raw('COALESCE(sent_at, failed_at)'))
            ->value(DB::raw('COALESCE(sent_at, failed_at)'));

        $guestPending = (int) GuestApplication::query()->where('converted_to_student', false)->count();
        $guestConverted24h = (int) GuestApplication::query()
            ->where('converted_to_student', true)
            ->where('updated_at', '>=', $todayStart)
            ->count();

        $studentActive = (int) StudentAssignment::query()->where('is_archived', false)->count();
        $studentUnassigned = (int) StudentAssignment::query()
            ->where('is_archived', false)
            ->whereNull('senior_email')
            ->count();

        $schedulerInfo = $this->readSchedulerHealth($now);
        $smokeInfo = $this->readMvpSmokeHealth($now);
        $apiRegressionInfo = $this->readApiRegressionHealth($now);
        $criticalCheckInfo = $this->readCriticalCheckHealth($now);

        $hasFailures = ($failedJobs > 0 || $notificationFailed > 0);
        $isQueueStuck = ($notificationQueued > 0 && $oldestQueueDelayMinutes >= 15);
        $isSchedulerStale = (bool) ($schedulerInfo['is_stale'] ?? false);
        $isSmokeFailed = (bool) ($smokeInfo['last_result_is_fail'] ?? false);
        $isApiRegressionFailed = (bool) ($apiRegressionInfo['last_result_is_fail'] ?? false);
        $isCriticalCheckFailed = (bool) ($criticalCheckInfo['last_result_is_fail'] ?? false);
        $level = ($hasFailures || $isQueueStuck || $isSchedulerStale || $isSmokeFailed || $isApiRegressionFailed || $isCriticalCheckFailed) ? 'warning' : 'ok';

        return response()->json([
            'timestamp' => $now->toDateTimeString(),
            'jobs' => [
                'queued' => $jobsQueued,
                'failed' => $failedJobs,
            ],
            'notifications' => [
                'queued' => $notificationQueued,
                'failed' => $notificationFailed,
                'sent_24h' => $notificationSent24h,
                'oldest_queue_delay_min' => $oldestQueueDelayMinutes,
                'last_dispatch_activity_at' => $lastDispatchActivityAt,
            ],
            'guest_pipeline' => [
                'pending_conversion' => $guestPending,
                'converted_24h' => $guestConverted24h,
            ],
            'student_pipeline' => [
                'active_assignments' => $studentActive,
                'unassigned_active' => $studentUnassigned,
            ],
            'automation' => [
                'scheduler' => $schedulerInfo,
                'mvp_smoke' => $smokeInfo,
                'api_regression' => $apiRegressionInfo,
                'critical_check' => $criticalCheckInfo,
            ],
            'health' => [
                'is_ok' => ($level === 'ok'),
                'level' => $level,
                'queue_stuck' => $isQueueStuck,
                'scheduler_stale' => $isSchedulerStale,
                'smoke_failed' => $isSmokeFailed,
                'api_regression_failed' => $isApiRegressionFailed,
                'critical_check_failed' => $isCriticalCheckFailed,
            ],
        ]);
    }

    /**
     * GET /api/v1/config/system-health/failed-jobs
     * Son başarısız job'ları listeler — admin panel izleme için.
     */
    public function failedJobs(): \Illuminate\Http\JsonResponse
    {
        if (!Schema::hasTable('failed_jobs')) {
            return response()->json(['ok' => true, 'total' => 0, 'rows' => []]);
        }

        $total = (int) DB::table('failed_jobs')->count();
        $recent = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get(['id', 'uuid', 'payload', 'exception', 'failed_at'])
            ->map(function ($row): array {
                try {
                    $payload = json_decode((string) $row->payload, true);
                    $jobClass = $payload['displayName'] ?? 'Unknown';
                } catch (\Throwable) {
                    $jobClass = 'Unknown';
                }

                $exceptionFirstLine = explode("\n", (string) ($row->exception ?? ''))[0] ?? '';

                return [
                    'id'         => $row->id,
                    'uuid'       => $row->uuid,
                    'job'        => $jobClass,
                    'error'      => $exceptionFirstLine,
                    'failed_at'  => $row->failed_at,
                ];
            });

        return response()->json([
            'ok'    => true,
            'total' => $total,
            'rows'  => $recent,
        ]);
    }

    private function readSchedulerHealth(Carbon $now): array
    {
        $path = storage_path('logs/scheduler.log');
        if (!File::exists($path)) {
            return [
                'log_exists' => false,
                'last_modified_at' => null,
                'last_modified_ago_min' => null,
                'is_stale' => true,
            ];
        }

        $lastModifiedTs = File::lastModified($path);
        $lastModified = Carbon::createFromTimestamp($lastModifiedTs);
        $agoMin = $lastModified->diffInMinutes($now);

        return [
            'log_exists' => true,
            'last_modified_at' => $lastModified->toDateTimeString(),
            'last_modified_ago_min' => $agoMin,
            'is_stale' => $agoMin >= 3,
        ];
    }

    private function readMvpSmokeHealth(Carbon $now): array
    {
        $path = storage_path('logs/mvp-smoke.log');
        if (!File::exists($path)) {
            return [
                'log_exists' => false,
                'last_result' => null,
                'last_result_at' => null,
                'last_result_age_hours' => null,
                'last_result_is_fail' => false,
                'stale' => true,
            ];
        }

        $lastModifiedTs = File::lastModified($path);
        $lastModified = Carbon::createFromTimestamp($lastModifiedTs);
        $lastLines = $this->readTailLines($path, 80);

        $lastResult = null;
        foreach (array_reverse($lastLines) as $line) {
            if (str_contains($line, 'MVP smoke SONUC: PASS')) {
                $lastResult = 'PASS';
                break;
            }
            if (str_contains($line, 'MVP smoke SONUC: FAIL')) {
                $lastResult = 'FAIL';
                break;
            }
        }

        $ageHours = $lastModified->diffInHours($now);

        return [
            'log_exists' => true,
            'last_result' => $lastResult,
            'last_result_at' => $lastModified->toDateTimeString(),
            'last_result_age_hours' => $ageHours,
            'last_result_is_fail' => $lastResult === 'FAIL',
            'stale' => $ageHours >= 30,
        ];
    }

    private function readApiRegressionHealth(Carbon $now): array
    {
        $path = storage_path('logs/api-regression.log');
        if (!File::exists($path)) {
            return [
                'log_exists' => false,
                'last_result' => null,
                'last_result_at' => null,
                'last_result_age_hours' => null,
                'last_result_is_fail' => false,
                'stale' => true,
            ];
        }

        $lastModifiedTs = File::lastModified($path);
        $lastModified = Carbon::createFromTimestamp($lastModifiedTs);
        $lastLines = $this->readTailLines($path, 80);

        $lastResult = null;
        foreach (array_reverse($lastLines) as $line) {
            if (str_contains($line, 'API regression smoke SONUC: PASS')) {
                $lastResult = 'PASS';
                break;
            }
            if (str_contains($line, 'API regression smoke SONUC: FAIL')) {
                $lastResult = 'FAIL';
                break;
            }
        }

        $ageHours = $lastModified->diffInHours($now);

        return [
            'log_exists' => true,
            'last_result' => $lastResult,
            'last_result_at' => $lastModified->toDateTimeString(),
            'last_result_age_hours' => $ageHours,
            'last_result_is_fail' => $lastResult === 'FAIL',
            'stale' => $ageHours >= 30,
        ];
    }

    private function readCriticalCheckHealth(Carbon $now): array
    {
        $path = storage_path('logs/critical-check.log');
        if (!File::exists($path)) {
            return [
                'log_exists' => false,
                'last_result' => null,
                'last_result_at' => null,
                'last_result_age_hours' => null,
                'last_result_is_fail' => false,
                'stale' => true,
            ];
        }

        $lastModifiedTs = File::lastModified($path);
        $lastModified = Carbon::createFromTimestamp($lastModifiedTs);
        $lastLines = $this->readTailLines($path, 120);

        $lastResult = null;
        foreach (array_reverse($lastLines) as $line) {
            if (str_contains($line, 'ops:critical-check SONUC: PASS')) {
                $lastResult = 'PASS';
                break;
            }
            if (str_contains($line, 'ops:critical-check SONUC: FAIL')) {
                $lastResult = 'FAIL';
                break;
            }
        }

        $ageHours = $lastModified->diffInHours($now);

        return [
            'log_exists' => true,
            'last_result' => $lastResult,
            'last_result_at' => $lastModified->toDateTimeString(),
            'last_result_age_hours' => $ageHours,
            'last_result_is_fail' => $lastResult === 'FAIL',
            'stale' => $ageHours >= 30,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function readTailLines(string $path, int $maxLines = 80): array
    {
        $content = (string) File::get($path);
        if ($content === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        return array_slice($lines, -1 * $maxLines);
    }
}
