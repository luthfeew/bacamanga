<?php

use App\Http\Controllers\ImageProxyController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/browse', 'pages::browse')->name('browse');
Route::livewire('/series/{slug}', 'pages::series-detail')->name('series.show');
Route::livewire('/series/{slug}/chapter/{chapterIndex}', 'pages::reader')->name('series.chapter');
Route::livewire('/library', 'pages::library')->name('library');
Route::get('/proxy/image', ImageProxyController::class)->name('proxy.image');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
