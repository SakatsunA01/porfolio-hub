<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerces', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
            $table->json('brand_palette')->nullable()->after('logo_path');
            $table->text('manifesto_text')->nullable()->after('brand_palette');
            $table->text('philosophy_text')->nullable()->after('manifesto_text');
            $table->text('contact_text')->nullable()->after('philosophy_text');
            $table->text('team_text')->nullable()->after('contact_text');
        });
    }

    public function down(): void
    {
        Schema::table('commerces', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'brand_palette',
                'manifesto_text',
                'philosophy_text',
                'contact_text',
                'team_text',
            ]);
        });
    }
};
