<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

/**
 * Almanya 9 şehrinin hero_video_id'sini gerçek tanıtım videolarıyla günceller.
 * Önceden hepsi 'LzLOhMsjpsw' placeholder ile aynıydı.
 *
 * Idempotent: data JSON kolonunda sadece hero_video_id alanını overwrite eder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('germany_cities')) {
            return;
        }

        $map = [
            'berlin'     => 'hlYbP-BL3tg',
            'munich'     => 'fkiCdD1MRTc',
            'hamburg'    => '4hp5OgLn0IY',
            'cologne'    => 'P9hKIFaDSU8',
            'frankfurt'  => 'R3Xt0ivyZCs',
            'stuttgart'  => 'B0_aLXLyaQ4',
            'dusseldorf' => 'cIXFh4mV9S8',
            'dresden'    => 'VQuOZzpipzg',
            'nurnberg'   => 'QQ4c2CpBZlg',
        ];

        foreach ($map as $slug => $videoId) {
            $row = DB::table('germany_cities')->where('slug', $slug)->first();
            if (!$row) {
                continue;
            }
            $data = json_decode((string) $row->data, true) ?: [];
            $data['hero_video_id'] = $videoId;

            DB::table('germany_cities')
                ->where('slug', $slug)
                ->update([
                    'data'       => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }

        try {
            Cache::forget('germany_cities_all');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
    }
};
