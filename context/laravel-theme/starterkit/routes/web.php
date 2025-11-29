<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\Controller;

Route::get('/', function () {
    return view('welcome');
});

/******** Dashboards ********/
Route::get('/', function () {
    return redirect('index'); // This will redirect '/' to '/index'
});

// Route::get('/', [DashboardsController::class, 'index']);
Route::get('index', [DashboardsController::class, 'index']);