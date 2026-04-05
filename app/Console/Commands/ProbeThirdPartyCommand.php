<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\MarketingAdminSetting;
use App\Models\MarketingIntegrationConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ProbeThirdPartyCommand extends Command
{
    protected $signature = 'marketing:probe-third-party {--company-limit=200 : Max companies to probe}';
    protected $description = 'Probe Calendly/Mailchimp/ClickUp connectivity and update integration health';

    public function handle(): int
    {
        $companyLimit = max(1, (int) $this->option('company-limit'));

        $companies = Company::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit($companyLimit)
            ->get(['id']);

        $checked  = 0;
        $connected = 0;
        $errors   = 0;
        $pending  = 0;
        $disabled = 0;

        $settingValue = function (int $companyId, string $key): string {
            $row = MarketingAdminSetting::query()
                ->forCompany($companyId)
                ->where('setting_key', $key)
                ->first(['setting_value']);

            return trim((string) data_get($row, 'setting_value.value', ''));
        };

        foreach ($companies as $company) {
            $companyId = (int) $company->id;

            $providers = [
                'calendly'  => [
                    'enabled'     => filter_var($settingValue($companyId, 'ext_calendly_enabled'), FILTER_VALIDATE_BOOLEAN),
                    'token'       => $settingValue($companyId, 'ext_calendly_api_key'),
                    'account_ref' => 'calendly',
                ],
                'mailchimp' => [
                    'enabled'     => filter_var($settingValue($companyId, 'ext_mailchimp_enabled'), FILTER_VALIDATE_BOOLEAN),
                    'token'       => $settingValue($companyId, 'ext_mailchimp_api_key'),
                    'account_ref' => 'mailchimp',
                ],
                'clickup'   => [
                    'enabled'     => filter_var($settingValue($companyId, 'ext_clickup_enabled'), FILTER_VALIDATE_BOOLEAN),
                    'token'       => $settingValue($companyId, 'ext_clickup_api_key'),
                    'account_ref' => 'clickup',
                ],
            ];

            foreach ($providers as $provider => $cfg) {
                $checked++;
                $status    = 'disabled';
                $lastError = null;

                if (!$cfg['enabled']) {
                    $disabled++;
                } elseif (trim((string) $cfg['token']) === '') {
                    $status    = 'pending';
                    $lastError = 'token missing';
                    $pending++;
                } else {
                    try {
                        $ok      = false;
                        $message = null;

                        if ($provider === 'calendly') {
                            $res = Http::timeout(20)
                                ->withHeaders(['Authorization' => 'Bearer ' . $cfg['token']])
                                ->get('https://api.calendly.com/users/me');
                            $ok = $res->successful();
                            if (!$ok) { $message = 'http ' . $res->status(); }
                        } elseif ($provider === 'mailchimp') {
                            $parts = explode('-', (string) $cfg['token']);
                            $dc    = trim((string) end($parts));
                            if ($dc === '' || count($parts) < 2) {
                                $ok      = false;
                                $message = 'invalid key format';
                            } else {
                                $res = Http::timeout(20)
                                    ->withBasicAuth('mentorde', (string) $cfg['token'])
                                    ->get('https://' . $dc . '.api.mailchimp.com/3.0/ping');
                                $ok = $res->successful();
                                if (!$ok) { $message = 'http ' . $res->status(); }
                            }
                        } elseif ($provider === 'clickup') {
                            $res = Http::timeout(20)
                                ->withHeaders(['Authorization' => (string) $cfg['token']])
                                ->get('https://api.clickup.com/api/v2/user');
                            $ok = $res->successful();
                            if (!$ok) { $message = 'http ' . $res->status(); }
                        }

                        if ($ok) {
                            $status = 'connected';
                            $connected++;
                        } else {
                            $status    = 'error';
                            $lastError = $message ?: 'connection failed';
                            $errors++;
                        }
                    } catch (\Throwable $e) {
                        $status    = 'error';
                        $lastError = mb_substr($e->getMessage(), 0, 500);
                        $errors++;
                    }
                }

                MarketingIntegrationConnection::query()->updateOrCreate(
                    ['company_id' => $companyId, 'provider' => $provider],
                    [
                        'auth_mode'       => 'manual',
                        'is_enabled'      => (bool) $cfg['enabled'],
                        'status'          => $status,
                        'account_ref'     => (string) $cfg['account_ref'],
                        'access_token'    => trim((string) $cfg['token']) !== '' ? (string) $cfg['token'] : null,
                        'last_checked_at' => now(),
                        'last_error'      => $lastError,
                    ]
                );
            }
        }

        $this->info(sprintf(
            'marketing:probe-third-party tamamlandi | checked:%d | connected:%d | pending:%d | disabled:%d | errors:%d',
            $checked, $connected, $pending, $disabled, $errors
        ));

        return 0;
    }
}
