<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOwnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();  // Primary key
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('number');
            $table->string('profile_picture')->nullable();  // Optional field for profile picture
            $table->string('username')->unique();
            $table->string('password');
            $table->date('date_of_birth');
            $table->timestamps();  // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('owners');
    }
}

