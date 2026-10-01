<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('full_name'); // Client's full name
            $table->string('service_name'); // Service name
            $table->json('selected_services')->nullable(); // Selected services
            $table->json('selected_staff')->nullable(); // Selected staff
            $table->integer('rating'); // Star rating (1-5)
            $table->text('review_message'); // Review message
            $table->timestamps(); // Created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
}
