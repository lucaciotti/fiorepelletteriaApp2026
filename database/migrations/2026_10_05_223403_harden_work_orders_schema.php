<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalizza eventuali NULL prima di rendere la colonna NOT NULL.
        DB::table('work_orders')->whereNull('paused')->update(['paused' => false]);

        Schema::table('work_orders', function (Blueprint $table): void {
            $table->boolean('paused')->default(false)->nullable(false)->change();

            $table->index(['operator_id', 'end_at'], 'work_orders_operator_end_index');
            $table->index(['end_at', 'paused'], 'work_orders_end_paused_index');
            $table->index('start_at', 'work_orders_start_at_index');
        });

        Schema::table('work_orders_record_time', function (Blueprint $table): void {
            $table->index(['work_order_id', 'end_at'], 'record_time_work_order_end_index');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropIndex('work_orders_operator_end_index');
            $table->dropIndex('work_orders_end_paused_index');
            $table->dropIndex('work_orders_start_at_index');

            $table->boolean('paused')->nullable()->default(false)->change();
        });

        Schema::table('work_orders_record_time', function (Blueprint $table): void {
            $table->dropIndex('record_time_work_order_end_index');
        });
    }
};
