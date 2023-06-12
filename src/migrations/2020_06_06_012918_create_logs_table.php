<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('slug');
                $table->longText('log');
                $table->integer('model_id');
                $table->longText('model');
                $table->string('device')->nullable();
                $table->timestamps();
            });
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
