<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('minecraft_toolkit_profiles')) {
            Schema::create('minecraft_toolkit_profiles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('software_json');
                $table->json('packages_json');
                $table->json('setup_json')->nullable();
                $table->boolean('shared')->default(false)->index();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (Schema::hasTable('minecraft_toolkit_packages') && ! Schema::hasColumn('minecraft_toolkit_packages', 'admin_notes')) {
            Schema::table('minecraft_toolkit_packages', fn (Blueprint $table) => $table->text('admin_notes')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('minecraft_toolkit_packages') && Schema::hasColumn('minecraft_toolkit_packages', 'admin_notes')) {
            Schema::table('minecraft_toolkit_packages', fn (Blueprint $table) => $table->dropColumn('admin_notes'));
        }
        Schema::dropIfExists('minecraft_toolkit_profiles');
    }
};
