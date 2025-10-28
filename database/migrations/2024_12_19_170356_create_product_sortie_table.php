<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_sortie', function (Blueprint $table) {

            $table->integer('product_id')->index();
            $table->integer('location_id')->index();
            $table->integer('transaction_id')->index();
            $table->decimal('quantity', 15, 2);
            $table->string('demendeur');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_sortie');
    }
};