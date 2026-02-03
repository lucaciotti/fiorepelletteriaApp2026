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
            $table->foreignId('process_type_id')->constrained();
            $table->double('total_hours')->unsigned()->nullable()->change();
            $table->timestamp('start_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->integer('total_hours')->unsigned()->nullable()->change();
            $table->timestamp('start_at')->useCurrent()->change();
            $table->dropColumn('process_type_id');
        });
    }
};
