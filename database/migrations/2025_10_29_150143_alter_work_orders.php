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
            $table->dropColumn(['customer_id', 'ord_num', 'product_id']);
            $table->boolean('paused')->nullable()->default(false);
            $table->dropColumn(['operator_id']);
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete()->after('id');
            $table->foreignId('order_row_id')->constrained()->cascadeOnDelete()->after('id');
            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['paused', 'order_id', 'order_row_id']);
            $table->dropColumn(['operator_id']);
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete()->after('id');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->after('id');
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete()->after('id');
            $table->string('ord_num')->after('id');
        });
    }
};
