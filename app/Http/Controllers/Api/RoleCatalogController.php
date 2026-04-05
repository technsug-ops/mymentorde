<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class RoleCatalogController extends Controller
{
    public function index()
    {
        return response()->json([
            'groups' => User::ROLE_GROUPS,
            'all_roles' => collect(User::ROLE_GROUPS)
                ->flatMap(fn ($g) => array_merge([$g['parent']], $g['children'] ?? []))
                ->unique()
                ->values()
                ->all(),
        ]);
    }
}
