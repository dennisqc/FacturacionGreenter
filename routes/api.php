<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Ruta básica para probar
Route::get('/ping', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

// Ruta protegida por Sanctum (si estás usando autenticación)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
