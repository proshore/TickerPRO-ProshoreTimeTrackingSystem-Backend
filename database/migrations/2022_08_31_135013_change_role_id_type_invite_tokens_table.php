<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invite_tokens', function (Blueprint $table) {
            $table->foreignId('role_id')->change()
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invite_tokens', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::table('invite_tokens', function (Blueprint $table) {
            $table->integer('role_id')->unsigned()->change();
        });
    }
};
