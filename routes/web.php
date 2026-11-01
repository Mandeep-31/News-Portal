<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('App is running', 200)
        ->header('Content-Type', 'text/plain');
})->name('home');
