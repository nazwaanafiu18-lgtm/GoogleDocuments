<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::get('/', [DocumentController::class, 'index'])->name('home');
Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
Route::post('/set-name', [DocumentController::class, 'setName'])->name('set-name');
Route::post('/documents/{document}/revisions', [DocumentController::class, 'saveRevision'])->name('documents.revisions.store');
Route::get('/documents/{document}/revisions', [DocumentController::class, 'getRevisions'])->name('documents.revisions.index');
Route::get('/revisions/{revision}', [DocumentController::class, 'getRevision'])->name('revisions.show');