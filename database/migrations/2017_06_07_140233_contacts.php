<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Contacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('contacts', function (Blueprint $table) 
      {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
        $table->longtext('message');
        $table->boolean('read')->default(false);
        $table->boolean('resolved')->default(false);
        $table->boolean('replied')->default(false);
        $table->boolean('warrent_reply')->default(false);
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
      Schema::dropIfExists('contacts');
    }
}
