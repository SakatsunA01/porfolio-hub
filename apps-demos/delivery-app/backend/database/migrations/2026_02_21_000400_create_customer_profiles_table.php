<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('customer_key')->unique();
            $table->string('display_name');
            $table->string('last_address')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};

