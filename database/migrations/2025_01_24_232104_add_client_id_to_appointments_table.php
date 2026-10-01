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
            // Add client_id column after category_id
            $table->unsignedBigInteger('client_id')->nullable()->after('category_id');

            // Add a foreign key constraint to reference clients table
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['client_id']);
            
            // Drop the client_id column
            $table->dropColumn('client_id');
        });
    }
};
