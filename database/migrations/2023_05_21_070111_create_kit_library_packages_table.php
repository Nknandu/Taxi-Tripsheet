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
        Schema::create('kit_library_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kit_library_id')->nullable();
            $table->foreign('kit_library_id')->references('id')->on('kit_libraries')->cascadeOnDelete();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
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
        Schema::dropIfExists('kit_library_packages');
    }
};
