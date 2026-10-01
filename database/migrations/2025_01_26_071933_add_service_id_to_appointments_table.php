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
        Schema::table('appointments', function (Blueprint $table) {
            // Add the service_id column after client_id
            $table->unsignedBigInteger('service_id')->nullable()->after('client_id');
            
            // Add a foreign key constraint linking service_id to the services table
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the foreign key and the column during rollback
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
