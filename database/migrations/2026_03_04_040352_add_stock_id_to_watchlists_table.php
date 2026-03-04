<?php

use App\Models\Stock;
use App\Models\Watchlist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add nullable stock_id column
        Schema::table('watchlists', function (Blueprint $table) {
            $table->foreignId('stock_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        // 2. Migrate existing data
        foreach (Watchlist::all() as $watchlist) {
            $stock = Stock::firstOrCreate(
                ['symbol' => $watchlist->symbol],
                ['company_name' => $watchlist->company_name]
            );
            $watchlist->update(['stock_id' => $stock->id]);
        }

        // 3. Make stock_id non-nullable and drop old columns
        Schema::table('watchlists', function (Blueprint $table) {
            $table->foreignId('stock_id')->nullable(false)->change();
            $table->dropUnique(['user_id', 'symbol']);
            $table->dropColumn(['symbol', 'company_name']);
        });

        // 4. Add new unique constraint
        Schema::table('watchlists', function (Blueprint $table) {
            $table->unique(['user_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::table('watchlists', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'stock_id']);
            $table->string('symbol')->after('user_id');
            $table->string('company_name')->after('symbol');
        });

        foreach (Watchlist::with('stock')->get() as $watchlist) {
            $watchlist->update([
                'symbol' => $watchlist->stock->symbol,
                'company_name' => $watchlist->stock->company_name,
            ]);
        }

        Schema::table('watchlists', function (Blueprint $table) {
            $table->unique(['user_id', 'symbol']);
            $table->dropConstrainedForeignId('stock_id');
        });
    }
};
