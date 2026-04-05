<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dev ortamı: display_name'i boş olan student_assignments kayıtlarına
 * gerçekçi Türkçe test isimleri atar.
 * Üretim ortamında zaten GuestApplication backfill ile dolmuş olur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('student_assignments', 'display_name')) {
            return;
        }

        $names = [
            'Ahmet Yılmaz', 'Fatma Kaya', 'Mehmet Demir', 'Ayşe Çelik', 'Mustafa Şahin',
            'Zeynep Yıldız', 'Ali Öztürk', 'Emine Arslan', 'Hüseyin Doğan', 'Hatice Kılıç',
            'İbrahim Aydın', 'Merve Erdoğan', 'Murat Çetin', 'Elif Korkmaz', 'Ömer Özkan',
            'Büşra Güneş', 'Serkan Yalçın', 'Selin Koç', 'Emre Acar', 'Deniz Polat',
            'Kadir Taş', 'Gizem Aksoy', 'Volkan Şimşek', 'Ceren Kurt', 'Barış Güler',
            'Pınar Yavuz', 'Tolga Aktaş', 'Derya Özdemir', 'Sinan Boz', 'Esra Kara',
            'Okan Bulut', 'Tuğçe Avcı', 'Cem Duman', 'Sevinç Güzel', 'Uğur Parlak',
            'Neslihan Ersoy', 'Taner Uysal', 'Melis Bozkurt', 'Kerem Kaplan', 'Şeyma Arslan',
            'Furkan Türk', 'Özge Mutlu', 'Serhat Demirkaya', 'Nurgül Çakır', 'Bora Işık',
            'Arzu Yücel', 'Berk Altın', 'İrem Ertürk', 'Onur Sari', 'Nazlı Dinç',
            'Kaan Aygün', 'Sibel Karakaş', 'Yusuf Gündüz', 'Cansu Yılmaz',
        ];

        $unnamed = DB::table('student_assignments')
            ->whereNull('display_name')
            ->orderBy('id')
            ->pluck('student_id');

        foreach ($unnamed as $i => $sid) {
            $name = $names[$i % count($names)];
            DB::table('student_assignments')
                ->where('student_id', $sid)
                ->update(['display_name' => $name]);
        }
    }

    public function down(): void
    {
        // Backfill'den gelen gerçek isimleri koruyalım — sadece null yap
        // (reverting not fully possible without tracking which were set by this migration)
    }
};
