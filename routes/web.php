<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IdentifyController;
//use App\Http\Controllers\FtirPeakController;
use App\Http\Controllers\FtirPeaksController;
use App\Http\Controllers\UserHomeController;






// Página principal
Route::get('/', [HomeController::class, 'index'])->name('home');

// Agrupar rutas que requieren autenticación
Route::middleware('auth')->group(function () {
    
    // Rutas para la carga y manejo de archivos
    Route::get('/subir', [FileController::class, 'showUploadForm'])->name('show.upload.form');
    Route::post('/subir', [FileController::class, 'handleFileUpload'])->name('handle.upload');

    // Rutas para el buscador FTIR
    Route::get('/buscadorFTIR', [SearchController::class, 'index'])->name('buscadorFTIR');
    Route::post('/api/search/ftir', [SearchController::class, 'search'])->name('ftir.search');
    Route::get('/download-csv', [SearchController::class, 'downloadCsv'])->name('download.csv');

    // Rutas para la comparación de espectros FTIR
    //Route::get('/comparar', [ComparisonController::class, 'showComparisonForm'])->name('show.comparison.form');
    Route::post('/api/search/compare', [ComparisonController::class, 'search'])->name('compare.search');
    Route::get('/comparar', [ComparisonController::class, 'showComparisonForm'])->name('compare.form'); 

      // Dashboard y perfil de usuario
        Route::get('/panel', function () {return view('home', ['user' => auth()->user()]);})->name('panel');
  
    //Route::get('/dashboard', function () {return view('dashboard');})->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    //para identificar
    Route::get('/identificar', [IdentifyController::class, 'index'])->name('ftir.identificar');
    
    //picos grupos funcionales
    
    //Route::get('/ftir/picos', [FtirPeakController::class, 'form'])->name('ftir.picos.form');
    //Route::post('/ftir/picos', [FtirPeakController::class, 'analyze'])->name('ftir.picos.analyze');

    Route::get('/identificar-grupos', [IdentifyController::class, 'grupos'])->name('identificar.grupos');
   // Route::post('/ftir/peaks-upload', [FtirPeaksController::class, 'peaksUpload']);
    



});

// Rutas de autenticación
require __DIR__.'/auth.php';
