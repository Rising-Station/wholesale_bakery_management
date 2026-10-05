<?php

use App\Http\Controllers\Admin\RetailerController;
use App\Http\Controllers\Admin\RoadController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// User & Role Management
Route::resource('roles', RoleController::class)->except(['create', 'show', 'edit']);
Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

// Phase 3: Roads & Retailers (Tier 3)
Route::resource('roads', RoadController::class)->except(['create', 'show', 'edit']);
Route::resource('retailers', RetailerController::class)->except(['create', 'edit']);
