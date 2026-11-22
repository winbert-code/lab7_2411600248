<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab 5 added status flags for accounts, brands and categories. Lab 6 uses
     * the same database, so every column is added only when it is missing.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status', 20)->default('active')->after('role');
            });
        }

        if (! Schema::hasColumn('categories', 'status')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('status', 20)->default('active')->after('description');
            });
        }

        if (! Schema::hasColumn('brands', 'status')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('status', 20)->default('active')->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('brands', 'status')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('categories', 'status')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
