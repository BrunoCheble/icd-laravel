<?php

use App\Http\Controllers\Site\ApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/announcements', [ApiController::class, 'index']);
});
