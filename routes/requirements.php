<?php

use Illuminate\Support\Facades\Route;

// =============================================================================
// REQUIREMENTS
// =============================================================================

Route::get('/requirements', function () {
    return view('requirements.index');
})->name('requirements.index');
