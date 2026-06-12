<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channel auth — 4 channel tipi:
|   user.{id}                → kullanıcının kendi kanalı (chat, kişisel toast)
|   senior.{seniorUserId}    → senior dashboard ping (yeni booking, iptal)
|   manager.{companyId}      → company-scope manager/marketing/owner ping (lead, payment)
|   platform.owner           → platform_owner tier sinyalleri
|
*/

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('senior.{seniorUserId}', function ($user, $seniorUserId) {
    return (int) $user->id === (int) $seniorUserId && (string) $user->role === 'senior';
});

Broadcast::channel('manager.{companyId}', function ($user, $companyId) {
    return (int) ($user->company_id ?? 0) === (int) $companyId
        && in_array((string) $user->role, ['manager', 'marketing_admin', 'platform_owner'], true);
});

Broadcast::channel('platform.owner', function ($user) {
    return (string) $user->role === 'platform_owner';
});
