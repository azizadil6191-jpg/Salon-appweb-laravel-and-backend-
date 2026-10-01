<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('id'); // Add category_id field
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade'); // Set foreign key
        });
    }
    
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['category_id']); // Drop foreign key
            $table->dropColumn('category_id'); // Drop category_id field
        });
    }
    
};
