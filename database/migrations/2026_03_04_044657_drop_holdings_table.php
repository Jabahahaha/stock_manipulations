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
        Schema::dropIfExists('holdings');
    }

    public function down(): void
    {
        Schema::create('holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->string('company_name');
            $table->decimal('quantity', 16, 6);
            $table->decimal('average_cost', 12, 2);
            $table->timestamps();
            $table->unique(['user_id', 'symbol']);
        });
    }
};
