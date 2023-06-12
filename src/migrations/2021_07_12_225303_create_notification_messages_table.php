<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Schema::create('notification_messages', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('subject');
                $table->longText('mail');
                $table->string('sms')->nullable();
                $table->string('action_label')->nullable();
                $table->string('action_url')->nullable();
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
        Schema::dropIfExists('notification_messages');
    }
}
