<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            $table->string('phone', 40)->nullable()->after('display_name');
            $table->string('apartment', 120)->nullable()->after('last_address');
            $table->string('address_reference')->nullable()->after('apartment');
            $table->text('delivery_notes')->nullable()->after('address_reference');
            $table->text('avatar_url')->nullable()->after('delivery_notes');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'phone',
                'apartment',
                'address_reference',
                'delivery_notes',
                'avatar_url',
            ]);
        });
    }
};
