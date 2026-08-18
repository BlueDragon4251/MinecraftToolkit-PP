<?php

declare(strict_types=1);

use BlueWolf\MinecraftToolkit\Http\Controllers\MinecraftToolkitStateController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/minecraft-toolkit')->middleware(['auth:api', 'throttle:60,1'])->group(function (): void {
    Route::get('/servers', [MinecraftToolkitStateController::class, 'index']);
    Route::get('/servers/{server}', [MinecraftToolkitStateController::class, 'show']);
});
