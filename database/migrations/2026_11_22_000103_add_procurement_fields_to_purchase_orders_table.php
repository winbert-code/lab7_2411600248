<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_orders', 'reference_number')) {
                $table->string('reference_number', 32)->nullable()->after('id');
            }

            if (! Schema::hasColumn('purchase_orders', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('supplier')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_orders', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('expected_date');
            }

            if (! Schema::hasColumn('purchase_orders', 'subtotal')) {
                $table->decimal('subtotal', 14, 2)->default(0)->after('expected_delivery_date');
                $table->decimal('tax_rate', 5, 2)->default(0)->after('subtotal');
                $table->decimal('tax_amount', 14, 2)->default(0)->after('tax_rate');
                $table->decimal('total', 14, 2)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('purchase_orders', 'currency')) {
                $table->string('currency', 3)->default('PHP')->after('total');
            }

            if (! Schema::hasColumn('purchase_orders', 'terms')) {
                $table->text('terms')->nullable()->after('notes');
                $table->text('rejection_reason')->nullable()->after('terms');
            }

            if (! Schema::hasColumn('purchase_orders', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_orders', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable()->after('approved_by');
                $table->timestamp('sent_at')->nullable()->after('approved_at');
                $table->date('received_date')->nullable()->after('sent_at');
                $table->unsignedTinyInteger('approval_level')->default(0)->after('received_date');
                $table->boolean('partially_received')->default(false)->after('approval_level');
            }

            if (! Schema::hasColumn('purchase_orders', 'deleted_at')) {
                $table->softDeletes();
            }

            // The previous lab already created some of these indexes; adding
            // them twice fails on MySQL, so only create what is missing.
            if (! Schema::hasIndex('purchase_orders', ['supplier_id', 'status'])) {
                $table->index(['supplier_id', 'status']);
            }

            if (! Schema::hasIndex('purchase_orders', ['status', 'order_date'])) {
                $table->index(['status', 'order_date']);
            }

            if (! Schema::hasIndex('purchase_orders', ['reference_number'])) {
                $table->index('reference_number');
            }
        });

        // Legacy rows from the previous lab keep their order number as reference.
        \Illuminate\Support\Facades\DB::table('purchase_orders')
            ->whereNull('reference_number')
            ->update(['reference_number' => \Illuminate\Support\Facades\DB::raw('order_number')]);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropIndex(['supplier_id', 'status']);
            $table->dropIndex(['status', 'order_date']);
            $table->dropIndex(['reference_number']);
            $table->dropColumn([
                'reference_number', 'supplier_id', 'expected_delivery_date', 'subtotal', 'tax_rate',
                'tax_amount', 'total', 'currency', 'terms', 'rejection_reason', 'created_by', 'approved_by',
                'approved_at', 'sent_at', 'received_date', 'approval_level', 'partially_received', 'deleted_at',
            ]);
        });
    }
};
