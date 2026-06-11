<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace Phase 7 — Senior review / rating sistemi.
 *
 * Tasarim:
 *   - public_bookings tamamlandiktan sonra invitee'ye signed-link mail gider,
 *     o link uzerinden bu tabloya 1 review yazilabilir (unique public_booking_id).
 *   - Manager moderation_status ile inceleyip onaylar/reddeder.
 *   - senior_booking_settings tablosuna avg_rating + total_reviews + completed_bookings
 *     cache kolonlari eklenir; SeniorReview model event'leri recompute eder.
 *
 * Addon-independent: tum kolonlar nullable / default, eski booking akisi etkilenmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('senior_reviews')) {
            Schema::create('senior_reviews', function (Blueprint $t): void {
                $t->id();
                $t->unsignedBigInteger('public_booking_id');
                $t->unsignedBigInteger('senior_user_id');
                $t->unsignedBigInteger('company_id');

                $t->string('reviewer_email', 200);
                $t->string('reviewer_name', 120);

                $t->unsignedTinyInteger('rating'); // 1-5
                $t->string('title', 150)->nullable();
                $t->text('body')->nullable();

                $t->boolean('is_public')->default(true);
                $t->boolean('is_verified')->default(true); // booking sahibi yazdiysa true

                $t->enum('moderation_status', ['pending', 'approved', 'rejected'])->default('approved');

                $t->timestamp('submitted_at')->nullable();
                $t->timestamp('moderated_at')->nullable();
                $t->string('moderation_note', 500)->nullable();

                $t->timestamps();

                $t->unique('public_booking_id', 'senior_reviews_booking_unique');
                $t->index(['senior_user_id', 'is_public', 'moderation_status'], 'sr_senior_visible_idx');
                $t->index(['company_id', 'moderation_status'], 'sr_company_mod_idx');
                $t->index(['rating'], 'sr_rating_idx');
            });
        }

        // senior_booking_settings: cache kolonlari
        if (Schema::hasTable('senior_booking_settings')) {
            Schema::table('senior_booking_settings', function (Blueprint $t): void {
                if (!Schema::hasColumn('senior_booking_settings', 'avg_rating')) {
                    $t->decimal('avg_rating', 3, 2)->nullable()->after('directory_order');
                }
                if (!Schema::hasColumn('senior_booking_settings', 'total_reviews')) {
                    $t->unsignedInteger('total_reviews')->default(0)->after('avg_rating');
                }
                if (!Schema::hasColumn('senior_booking_settings', 'total_completed_bookings')) {
                    $t->unsignedInteger('total_completed_bookings')->default(0)->after('total_reviews');
                }
            });

            // Listing sort + filter index — avg_rating DESC
            Schema::table('senior_booking_settings', function (Blueprint $t): void {
                try {
                    $t->index(['is_public', 'is_active', 'avg_rating'], 'sbs_rating_listing_idx');
                } catch (\Throwable $e) {
                    // zaten varsa
                }
            });
        }

        // public_bookings — completed_at kolonu (yoksa eklen)
        if (Schema::hasTable('public_bookings') && !Schema::hasColumn('public_bookings', 'completed_at')) {
            Schema::table('public_bookings', function (Blueprint $t): void {
                $t->timestamp('completed_at')->nullable()->after('canceled_at');
                $t->timestamp('review_request_sent_at')->nullable()->after('completed_at');
            });
            Schema::table('public_bookings', function (Blueprint $t): void {
                try {
                    $t->index(['completed_at', 'review_request_sent_at'], 'pb_review_request_idx');
                } catch (\Throwable $e) {
                    // skip
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('senior_booking_settings')) {
            Schema::table('senior_booking_settings', function (Blueprint $t): void {
                try { $t->dropIndex('sbs_rating_listing_idx'); } catch (\Throwable $e) {}
                foreach (['total_completed_bookings', 'total_reviews', 'avg_rating'] as $col) {
                    if (Schema::hasColumn('senior_booking_settings', $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('public_bookings')) {
            Schema::table('public_bookings', function (Blueprint $t): void {
                try { $t->dropIndex('pb_review_request_idx'); } catch (\Throwable $e) {}
                foreach (['review_request_sent_at', 'completed_at'] as $col) {
                    if (Schema::hasColumn('public_bookings', $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('senior_reviews');
    }
};
