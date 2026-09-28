<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('variant', 1);
            $table->timestamps();
        });
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->timestamp('paid_at')->nullable();
            $table->json('checklist')->nullable();
            $table->timestamps();
        });
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('assignment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->timestamps();
            $table->unique(['assignment_id', 'type']);
        });
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('mode', 10);
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('webhook_receipts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_receipts');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('assignments');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
