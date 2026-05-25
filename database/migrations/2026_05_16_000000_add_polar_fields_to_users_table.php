<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('polar_owner_id')->nullable()->after('remember_token');
            $table->string('polar_access_token')->nullable()->after('polar_owner_id');
            $table->string('polar_refresh_token')->nullable()->after('polar_access_token');
            $table->timestamp('polar_token_expires_at')->nullable()->after('polar_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'polar_owner_id',
                'polar_access_token',
                'polar_refresh_token',
                'polar_token_expires_at',
            ]);
        });
    }
};
