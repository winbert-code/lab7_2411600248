<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the columns needed for a complete stock-movement audit trail.
     */
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->unsignedInteger('previous_quantity')->nullable()->after('quantity');
            $table->unsignedInteger('balance_after')->nullable()->after('previous_quantity');
            $table->string('reason', 64)->nullable()->after('reference');
            $table->text('notes')->nullable()->after('reason');

            $table->index(['product_id', 'created_at'], 'inventory_transactions_product_created_index');
            $table->index('type', 'inventory_transactions_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('inventory_transactions_product_created_index');
            $table->dropIndex('inventory_transactions_type_index');
            $table->dropColumn(['previous_quantity', 'balance_after', 'reason', 'notes']);
        });
    }
};
