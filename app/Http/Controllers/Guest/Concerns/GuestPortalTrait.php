<?php

namespace App\Http\Controllers\Guest\Concerns;

use App\Models\GuestApplication;
use App\Services\GuestResolverService;
use App\Services\GuestViewDataService;
use Illuminate\Http\Request;

/**
 * Shared helpers for Guest portal sub-controllers.
 * Controllers using this trait must inject GuestResolverService and GuestViewDataService.
 */
trait GuestPortalTrait
{
    protected function resolveGuest(Request $request): ?GuestApplication
    {
        return $this->guestResolver->resolve($request);
    }

    protected function buildViewData(Request $request, ?GuestApplication $guest): array
    {
        return $this->viewData->build($request, $guest);
    }
}
