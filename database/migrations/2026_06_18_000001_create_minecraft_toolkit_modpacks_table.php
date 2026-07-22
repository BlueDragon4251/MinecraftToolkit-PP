<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minecraft_toolkit_modpacks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('server_uuid')->index();
            $table->foreignId('setup_id')->nullable()->constrained('minecraft_toolkit_setups')->nullOnDelete();
            $table->string('source')->index();
            $table->string('source_project_id')->nullable()->index();
            $table->string('source_version_id')->nullable();
            $table->string('name');
            $table->string('version_number')->nullable();
            $table->string('file_name');
            $table->text('download_url')->nullable();
            $table->string('minecraft_version')->nullable();
            $table->string('loader')->nullable();
            $table->string('loader_version')->nullable();
            $table->string('install_path')->default('/.minecraft-toolkit/modpacks/active');
            $table->string('archive_path')->nullable();
            $table->json('manifest_json')->nullable();
            $table->json('files_json')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->unsignedInteger('installed_by')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minecraft_toolkit_modpacks');
    }
};
