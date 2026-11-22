<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('company_name', 150)->index();
            $table->string('contact_person', 100);
            $table->string('email')->unique();
            $table->string('phone', 40);
            $table->string('address', 255);
            $table->string('city', 100)->nullable();
            $table->string('payment_terms', 40)->default('Net 30');
            $table->unsignedInteger('lead_time_days')->default(7)->index();
            $table->decimal('minimum_order_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'company_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
