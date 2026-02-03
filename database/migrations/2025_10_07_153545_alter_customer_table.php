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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('subname')->nullable()->change();
            $table->string('tva')->nullable()->change();
            $table->string('localita')->nullable()->change();
            $table->string('indirizzo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('subname')->change();
            $table->string('tva')->change();
            $table->string('localita')->change();
            $table->string('indirizzo')->change();
            //
        });
    }
};
