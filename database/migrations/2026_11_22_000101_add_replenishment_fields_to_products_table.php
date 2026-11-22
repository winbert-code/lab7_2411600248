<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'safety_stock')) {
                $table->unsignedInteger('safety_stock')->default(0)->after('reorder_level');
            }

            if (! Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 12, 2)->default(0)->after('unit_price');
            }

            if (! Schema::hasColumn('products', 'lead_time_days')) {
                $table->unsignedInteger('lead_time_days')->default(7)->after('cost_price');
            }

            if (! Schema::hasColumn('products', 'package_size')) {
                $table->unsignedInteger('package_size')->default(1)->after('lead_time_days');
            }

            if (! Schema::hasColumn('products', 'min_order_qty')) {
                $table->unsignedInteger('min_order_qty')->default(1)->after('package_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'supplier_id',
                'safety_stock',
                'cost_price',
                'lead_time_days',
                'package_size',
                'min_order_qty',
            ]);
        });
    }
};
