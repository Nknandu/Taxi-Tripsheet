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
        Schema::create('kit_library_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kit_library_id')->nullable();
            $table->foreign('kit_library_id')->references('id')->on('kit_libraries')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->longText('icon')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->foreign('created_by_admin_id')->references('id')->on('admins');
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->foreign('updated_by_admin_id')->references('id')->on('admins');
            $table->softDeletes();
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
        Schema::dropIfExists('kit_library_features');
    }
};
