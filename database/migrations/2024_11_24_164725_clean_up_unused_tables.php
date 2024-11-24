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
        Schema::dropIfExists('course_user_role');
        Schema::dropIfExists('course_role_permissions');


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('course_user_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_role_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('course_role_id')->references('id')->on('course_roles');
        });

        Schema::create('course_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_role_id');
            $table->unsignedSmallInteger('permission_level')->default(0);

            $table->foreign('course_role_id')->references('id')->on('course_roles');
        });
    }
};
