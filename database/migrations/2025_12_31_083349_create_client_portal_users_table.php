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
        Schema::create('client_portal_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->string('email');
            $table->string('password');
            $table->string('name');
            $table->string('role');
            $table->string('status')->default('invited');
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->foreignUlid('invited_by')->constrained('firm_users')->cascadeOnDelete();
            $table->rememberToken();
            $table->timestamps();

            $table->index('firm_id');
            $table->index('email');
            $table->index('status');
            $table->index(['firm_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_portal_users');
    }
};
