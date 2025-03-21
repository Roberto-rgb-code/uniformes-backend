<?php

use App\Http\Controllers\UniformeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('uniformes-destacados', UniformeController::class);
