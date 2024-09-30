<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {

            $table->id();
            $table->integer('model_id');
            $table->string('model_type');
            $table->string('location');
            $table->string('type')->nullable();
            $table->string('url')->nullable();
            $table->string('name');
            $table->string('extension')->nullable();
            $table->integer('size')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('updated_by')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('deleted_by')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
