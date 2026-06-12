<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Platform Owner — Global platform ayarları sayfası.
 *
 * Tabs: Genel / Faturalama / E-posta / Bildirim
 * Settings PlatformSetting model üzerinden persist edilir (cache'li).
 */
class PlatformSettingsController extends Controller
{
    /**
     * Tüm setting'leri kategoriler bazlı grupla → view.
     */
    public function index(): View
    {
        $rows = PlatformSetting::query()->orderBy('key')->get();

        // key → row map (UI'da hızlı erişim için)
        $map = $rows->keyBy('key');

        return view('platform.settings.index', [
            'rows'   => $rows,
            'map'    => $map,
        ]);
    }

    /**
     * Batch update — gelen tüm key-value pair'leri yaz, audit log düş.
     */
    public function update(Request $request): RedirectResponse
    {
        $payload = $request->input('settings', []);
        if (!is_array($payload) || empty($payload)) {
            return back()->with('error', 'Güncellenecek değer gönderilmedi.');
        }

        $allowedCategories = ['system', 'billing', 'email', 'notifications', 'security'];

        $changed = 0;
        $oldSnapshot = [];
        $newSnapshot = [];

        foreach ($payload as $key => $entry) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            // entry: ['value' => ..., 'category' => ..., 'is_secret' => 0/1]
            $value    = $entry['value']    ?? null;
            $category = $entry['category'] ?? 'system';
            if (!in_array($category, $allowedCategories, true)) {
                $category = 'system';
            }

            // Bool/int normalize — checkbox'tan "on"/"1", number input'tan "60" gibi gelir.
            if (in_array($value, ['true', 'on', '1'], true)) {
                $value = true;
            } elseif (in_array($value, ['false', 'off', '0'], true)) {
                $value = false;
            } elseif (is_string($value) && is_numeric($value) && !str_contains($value, '.')) {
                $value = (int) $value;
            } elseif (is_string($value) && is_numeric($value)) {
                $value = (float) $value;
            }

            // Special: daily_report_recipients comma-separated → array
            if ($key === 'platform.daily_report_recipients' && is_string($value)) {
                $value = array_values(array_filter(array_map('trim', explode(',', $value))));
            }

            // SMTP password — boş gönderildiyse değiştirme (mevcut korunsun)
            if ($key === 'platform.smtp_password' && (is_null($value) || $value === '')) {
                continue;
            }

            $old = PlatformSetting::get($key);
            if (json_encode($old) === json_encode($value)) {
                continue; // değişiklik yok
            }

            // is_secret ise eski değeri loglama
            $isSecret = (bool) ($entry['is_secret'] ?? false);
            $oldSnapshot[$key] = $isSecret ? '***' : $old;
            $newSnapshot[$key] = $isSecret ? '***' : $value;

            PlatformSetting::set($key, $value, $category);
            $changed++;
        }

        if ($changed > 0) {
            $this->logAudit('platform.settings.update', $oldSnapshot, $newSnapshot, $request);
        }

        return redirect()->route('platform.settings')
            ->with('success', $changed > 0 ? "{$changed} ayar güncellendi." : 'Değişiklik tespit edilmedi.');
    }

    /**
     * E-posta config test — basit ping. Hata varsa flash error.
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $to = $request->input('to') ?: PlatformSetting::get('platform.support_email', 'support@mentorde.com');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Geçerli bir e-posta adresi gerekli.');
        }

        try {
            Mail::raw(
                "MentorDE Platform Owner Console — SMTP test e-postası.\n\nGönderen: " . config('app.name'),
                function ($msg) use ($to) {
                    $msg->to($to)->subject('[MentorDE Platform] SMTP test');
                }
            );
            return back()->with('success', "Test e-postası {$to} adresine gönderildi.");
        } catch (\Throwable $e) {
            Log::warning('Platform SMTP test failed', ['err' => $e->getMessage()]);
            return back()->with('error', 'E-posta gönderilemedi: ' . $e->getMessage());
        }
    }

    /**
     * Audit log — yeni PlatformAuditLog'a yaz (platform owner gozu),
     * eski audit_trails'e de yaz (manager paneliyle entegrasyon icin),
     * her ikisi de fail ederse Log::info fallback.
     */
    private function logAudit(string $action, array $old, array $new, Request $request): void
    {
        // 1) Yeni platform audit log
        try {
            \App\Models\PlatformAuditLog::record(
                $action,
                [
                    'target_type' => 'platform_setting',
                    'old'         => $old,
                    'new'         => $new,
                    'changed_keys'=> array_keys($new),
                ],
                \App\Models\PlatformAuditLog::SEVERITY_WARNING
            );
        } catch (\Throwable $e) {
            // ignore — fallback'lar var
        }

        // 2) Eski audit_trails (manager paneli iceren ortak gorunum)
        try {
            if (Schema::hasTable('audit_trails')) {
                AuditTrail::create([
                    'company_id'  => null,
                    'user_id'     => auth()->id(),
                    'action'      => 'update',
                    'entity_type' => 'platform_setting',
                    'entity_id'   => $action,
                    'old_values'  => $old,
                    'new_values'  => $new,
                    'ip_address'  => $request->ip(),
                    'user_agent'  => substr((string) $request->userAgent(), 0, 500),
                    'request_url' => substr($request->fullUrl(), 0, 500),
                ]);
                return;
            }
        } catch (\Throwable $e) {
            // fall through to log
        }

        Log::info("[platform-audit] {$action}", [
            'user_id' => auth()->id(),
            'old'     => $old,
            'new'     => $new,
            'ip'      => $request->ip(),
        ]);
    }
}
