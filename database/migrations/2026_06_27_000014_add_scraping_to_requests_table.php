<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Provenance of the request. 'organic' = posted by a real buyer,
            // 'scraped' = imported from an external source (Google/Facebook/etc.).
            $table->string('source', 20)->default('organic')->after('id')->index();
            $table->string('source_platform', 40)->nullable()->after('source');
            $table->string('source_url', 1024)->nullable()->after('source_platform');
            $table->string('external_id')->nullable()->after('source_url');

            // Contact captured from the scraped post (so merchants/admin can reach out).
            $table->string('contact_name')->nullable()->after('city');
            $table->string('contact_phone', 40)->nullable()->after('contact_name');

            // Imported demand is free to engage with — no credit/commission charged.
            $table->boolean('commission_exempt')->default(false)->index()->after('warranty_required');
            $table->timestamp('imported_at')->nullable()->after('published_at');

            // Dedupe key: the same external post is never imported twice.
            $table->unique(['source_platform', 'external_id'], 'requests_source_unique');
        });

        // Scraped requests have no real buyer account.
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropUnique('requests_source_unique');
            $table->dropColumn([
                'source', 'source_platform', 'source_url', 'external_id',
                'contact_name', 'contact_phone', 'commission_exempt', 'imported_at',
            ]);
        });
    }
};
