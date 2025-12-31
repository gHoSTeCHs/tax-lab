<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('firm_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name');
            $table->string('role');
            $table->string('status')->default('active');
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->ulid('invited_by')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('firm_id');
            $table->index('email');
            $table->index('role');
            $table->index('status');
            $table->index(['firm_id', 'status']);
        });

        Schema::table('firm_users', function (Blueprint $table) {
            $table->foreign('invited_by')->references('id')->on('firm_users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firm_users');
    }
};
