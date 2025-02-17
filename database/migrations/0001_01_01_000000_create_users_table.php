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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('avatar')->nullable();
            $table->string('password')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expiry_at')->nullable();
            $table->string('dob')->comment('Date of Birth')->nullable();
            $table->boolean('is_notify')->default(true);
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->integer('max_distance')->default(100);
            $table->string('age_range')->default('{"min_age":"18","max_age":"40"}');
            $table->string('gender')->nullable();
            $table->string('dating_with')->nullable();
            $table->string('height')->comment('height in cm')->nullable();
            $table->string('passions')->nullable();
            $table->string('interests')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('have_children')->nullable();
            $table->string('home_town')->nullable();
            $table->string('work_place')->nullable();
            $table->string('job')->nullable();
            $table->string('school')->nullable();
            $table->string('edu_lvl')->nullable();
            $table->string('religion')->nullable();
            $table->string('drink')->nullable();
            $table->string('smoke')->nullable();
            $table->string('smoke_weed')->nullable();
            $table->string('drugs')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
