<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone_number');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->json('selected_staff')->nullable(); // Store selected staff as JSON
            $table->json('selected_services'); // Store selected services as JSON
            $table->string('service_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('Pending'); // Default status is Pending
            $table->timestamps(); // Adds created_at and updated_at

            // Foreign key constraint
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
