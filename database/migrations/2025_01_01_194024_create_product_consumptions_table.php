<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductConsumptionsTable extends Migration
{
    public function up()
    {
        Schema::create('product_consumptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index();
            $table->unsignedInteger('location_id')->index();
            $table->unsignedInteger('transaction_id')->index();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Adding unit price column
            $table->decimal('total_cost', 10, 2); // Adding total cost column
            $table->date('date')->nullable();
            $table->string('type', 50); // Assuming 'type' to be string with max length 50
            $table->text('notes')->nullable(); // Adding notes column
            $table->timestamps();

            // Adding foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_consumptions');
    }
}
