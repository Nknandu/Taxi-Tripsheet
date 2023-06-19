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
        Schema::create('kit_libraries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kit_type_id')->nullable();
            $table->foreign('kit_type_id')->references('id')->on('kit_types')->cascadeOnDelete();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('image')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('file')->nullable();
            $table->text('preview_link')->nullable();
            $table->string('tag_ids')->nullable();
            $table->longText('description')->nullable();
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
        Schema::dropIfExists('kit_libraries');
    }
};
