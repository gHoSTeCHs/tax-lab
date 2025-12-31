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
        Schema::create('firm_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->string('email');
            $table->string('role');
            $table->string('token')->unique();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->foreignUlid('invited_by')->constrained('firm_users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('firm_id');
            $table->index('email');
            $table->index('token');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firm_invitations');
    }
};
