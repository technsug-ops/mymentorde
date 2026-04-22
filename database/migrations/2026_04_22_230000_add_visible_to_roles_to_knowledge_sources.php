<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('knowledge_sources')) {
            return;
        }

        if (!Schema::hasColumn('knowledge_sources', 'visible_to_roles')) {
            Schema::table('knowledge_sources', function (Blueprint $t): void {
                // JSON array: ['guest', 'student', 'senior', 'manager', 'admin_staff']
                // Null/boş = tüm dış roller (guest + student) — geriye uyumluluk
                $t->json('visible_to_roles')->nullable()->after('target_audience');
            });
        }

        // Mevcut satırları target_audience'dan seed et
        $rows = DB::table('knowledge_sources')->select('id', 'target_audience', 'visible_to_roles')->get();
        foreach ($rows as $row) {
            if ($row->visible_to_roles) {
                continue; // zaten doldurulmuş
            }
            $roles = match ($row->target_audience) {
                'student' => ['student'],
                'guest'   => ['guest'],
                'both'    => ['guest', 'student'],
                default   => ['guest', 'student'],
            };
            DB::table('knowledge_sources')
                ->where('id', $row->id)
                ->update(['visible_to_roles' => json_encode($roles, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('knowledge_sources', 'visible_to_roles')) {
            Schema::table('knowledge_sources', function (Blueprint $t): void {
                $t->dropColumn('visible_to_roles');
            });
        }
    }
};
