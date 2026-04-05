<?php

namespace App\Services\Integrations\Contracts;

interface VideoConferenceInterface
{
    public function createMeeting(array $data): string;

    public function cancelMeeting(string $meetingId): bool;

    public function getJoinUrl(string $meetingId): string;

    public function handleWebhook(array $payload): void;
}

