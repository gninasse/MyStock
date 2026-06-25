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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();   // ENT-20240101-001
            $table->enum('type', ['entry', 'exit', 'inventory']);
            $table->foreignId('store_id')->constrained('stores');
            $table->enum('status', ['draft', 'validated', 'cancelled'])
                ->default('draft');
            $table->text('comment')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'type', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
