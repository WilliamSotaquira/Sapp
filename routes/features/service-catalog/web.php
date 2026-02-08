<?php

use App\Http\Controllers\ServiceFamilyController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubServiceController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// CATÁLOGO DE SERVICIOS
// =============================================================================

Route::resource('service-families', ServiceFamilyController::class);
Route::resource('companies', CompanyController::class);
Route::resource('contracts', ContractController::class);
Route::resource('services', ServiceController::class);
Route::resource('sub-services', SubServiceController::class);
