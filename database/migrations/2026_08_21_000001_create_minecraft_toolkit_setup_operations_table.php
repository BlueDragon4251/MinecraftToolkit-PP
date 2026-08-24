<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minecraft_toolkit_setup_operations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('server_id')->index();
            $table->uuid('server_uuid')->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedBigInteger('setup_id')->nullable();
            $table->unsignedBigInteger('backup_id')->nullable()->index();
            $table->string('status')->default('queued')->index();
            $table->string('stage')->default('queued');
            $table->json('payload_json');
            $table->string('icon_file')->nullable();
            $table->string('modpack_file')->nullable();
            $table->string('modpack_mode')->default('combine');
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('setup_id')->references('id')->on('minecraft_toolkit_setups')->nullOnDelete();
            $table->foreign('backup_id')->references('id')->on('backups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minecraft_toolkit_setup_operations');
    }
};
