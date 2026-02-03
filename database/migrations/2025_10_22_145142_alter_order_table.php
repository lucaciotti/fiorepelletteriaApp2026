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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->double('quantity')->nullable()->change();
            $table->renameColumn('total_hours', 'total_minutes');
        });
    }
    
    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->double('quantity')->change();
            $table->renameColumn('total_minutes', 'total_hours');
        });
    }
};
