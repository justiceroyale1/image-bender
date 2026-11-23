<?php

use App\Enums\Status;
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
        Schema::create('image_generations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique()->after('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('idempotency_key');
            $table->text('prompt');
            $table->string('quality');
            $table->string('size');
            $table->json('reference_images')->nullable();
            // queued -> processing -> succeeded -> failed
            $table->string('status')->default(Status::Queued->value);
            $table->text('image_url')->nullable();
            $table->string('provider_cost')->nullable();
            $table->json('provider_error')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();

            // Prevents duplicate client submissions per user
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_generations');
    }
};
