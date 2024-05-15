<?php

use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/contact', [SiteController::class, 'contact']);

Route::get('/', function () {
    return view('welcome');
});
