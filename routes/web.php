<?php

use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/auth/google/redirect', [SocialAuthController::class, 'redirect'])
    ->defaults('provider', 'google')
    ->name('auth.google.redirect');

Route::get('/api/auth/google/callback', [SocialAuthController::class, 'callback'])
    ->defaults('provider', 'google')
    ->name('auth.google.callback');
