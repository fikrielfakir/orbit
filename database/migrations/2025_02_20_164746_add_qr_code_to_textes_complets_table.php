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
    Schema::table('contacts', function (Blueprint $table) {
        $table->string('qr_code')->nullable()->after('updated_at');
    });
}

public function down()
{
    Schema::table('textes_complets', function (Blueprint $table) {
        $table->dropColumn('qr_code');
    });
}
};
