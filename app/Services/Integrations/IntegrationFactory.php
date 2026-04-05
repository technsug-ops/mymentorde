<?php

namespace App\Services\Integrations;

use App\Models\IntegrationConfig;
use App\Services\Integrations\Adapters\Calendar\CalComAdapter;
use App\Services\Integrations\Adapters\Calendar\CalendlyAdapter;
use App\Services\Integrations\Adapters\Calendar\GoogleCalendarAdapter;
use App\Services\Integrations\Adapters\EmailMarketing\MailchimpAdapter;
use App\Services\Integrations\Adapters\EmailMarketing\SendGridAdapter;
use App\Services\Integrations\Adapters\EmailMarketing\ZohoAdapter;
use App\Services\Integrations\Adapters\ESign\DocuSignAdapter;
use App\Services\Integrations\Adapters\ESign\HelloSignAdapter;
use App\Services\Integrations\Adapters\ESign\PandaDocAdapter;
use App\Services\Integrations\Adapters\ProjectManagement\ClickUpAdapter;
use App\Services\Integrations\Adapters\ProjectManagement\MondayAdapter;
use App\Services\Integrations\Adapters\ProjectManagement\NotionAdapter;
use App\Services\Integrations\Adapters\Video\GoogleMeetAdapter;
use App\Services\Integrations\Adapters\Video\TeamsAdapter;
use App\Services\Integrations\Adapters\Video\ZoomAdapter;
use App\Services\Integrations\Contracts\CalendarIntegrationInterface;
use App\Services\Integrations\Contracts\ElectronicSignatureInterface;
use App\Services\Integrations\Contracts\EmailMarketingInterface;
use App\Services\Integrations\Contracts\ProjectManagementInterface;
use App\Services\Integrations\Contracts\VideoConferenceInterface;
use RuntimeException;

class IntegrationFactory
{
    public function getCalendarService(): CalendarIntegrationInterface
    {
        $provider = $this->resolveProvider('calendar');

        return match ($provider) {
            'calendly' => new CalendlyAdapter(),
            'google_calendar' => new GoogleCalendarAdapter(),
            'cal_com' => new CalComAdapter(),
            default => throw new RuntimeException("Calendar provider unsupported: {$provider}"),
        };
    }

    public function getEmailMarketingService(): EmailMarketingInterface
    {
        $provider = $this->resolveProvider('email_marketing');

        return match ($provider) {
            'zoho' => new ZohoAdapter(),
            'mailchimp' => new MailchimpAdapter(),
            'sendgrid' => new SendGridAdapter(),
            default => throw new RuntimeException("Email marketing provider unsupported: {$provider}"),
        };
    }

    public function getProjectManagementService(): ProjectManagementInterface
    {
        $provider = $this->resolveProvider('crm');

        return match ($provider) {
            'clickup' => new ClickUpAdapter(),
            'monday' => new MondayAdapter(),
            'notion' => new NotionAdapter(),
            default => throw new RuntimeException("Project provider unsupported: {$provider}"),
        };
    }

    public function getElectronicSignatureService(): ElectronicSignatureInterface
    {
        $provider = $this->resolveProvider('esign');

        return match ($provider) {
            'docusign' => new DocuSignAdapter(),
            'pandadoc' => new PandaDocAdapter(),
            'hellosign' => new HelloSignAdapter(),
            default => throw new RuntimeException("ESign provider unsupported: {$provider}"),
        };
    }

    public function getVideoConferenceService(): VideoConferenceInterface
    {
        $provider = $this->resolveProvider('video');

        return match ($provider) {
            'zoom' => new ZoomAdapter(),
            'google_meet' => new GoogleMeetAdapter(),
            'teams' => new TeamsAdapter(),
            default => throw new RuntimeException("Video provider unsupported: {$provider}"),
        };
    }

    private function resolveProvider(string $category): string
    {
        $row = IntegrationConfig::query()
            ->where('category', $category)
            ->first();

        if (!$row) {
            throw new RuntimeException("Integration config missing: {$category}");
        }

        if (!(bool) $row->is_enabled) {
            throw new RuntimeException("Integration disabled: {$category}");
        }

        $provider = trim((string) ($row->active_provider ?? ''));
        if ($provider === '') {
            throw new RuntimeException("Active provider missing: {$category}");
        }

        return $provider;
    }
}

