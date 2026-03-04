<?php

use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add nullable stock_id column
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('stock_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        // 2. Migrate existing data
        foreach (Transaction::all() as $transaction) {
            $stock = Stock::firstOrCreate(
                ['symbol' => $transaction->symbol],
                ['company_name' => $transaction->company_name]
            );
            $transaction->update(['stock_id' => $stock->id]);
        }

        // 3. Make stock_id non-nullable and drop old columns
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('stock_id')->nullable(false)->change();
            $table->dropColumn(['symbol', 'company_name']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('symbol')->after('user_id');
            $table->string('company_name')->after('symbol');
        });

        foreach (Transaction::with('stock')->get() as $transaction) {
            $transaction->update([
                'symbol' => $transaction->stock->symbol,
                'company_name' => $transaction->stock->company_name,
            ]);
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_id');
        });
    }
};
