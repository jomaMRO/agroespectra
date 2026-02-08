<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FtirMatchController;
use App\Http\Controllers\FtirPeaksController;
use App\Http\Controllers\ComparisonController;


Route::post('/ftir/match', [FtirMatchController::class, 'match']);
Route::post('/ftir/match-upload', [FtirMatchController::class, 'matchUpload']);  
Route::post('ftir/peaks-upload', [FtirPeaksController::class, 'peaksUpload']);
Route::post('/ftir/compare', [ComparisonController::class, 'compareApi']);

