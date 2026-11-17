<?php

use App\Http\Controllers\ServiceWorkerController;
use App\Http\Middleware\EnsureOnboarded;
use App\Livewire;
use Illuminate\Support\Facades\Route;

// PWA
Route::get('/sw.js', ServiceWorkerController::class)->name('sw');
Route::view('/offline', 'offline')->name('offline');

Route::middleware(['auth', EnsureOnboarded::class])->group(function () {
    Route::get('/', Livewire\Today::class)->name('home');
    Route::get('/cursos', Livewire\Courses::class)->name('courses');
    Route::get('/estudar', Livewire\Study::class)->name('study');
    Route::get('/revisoes', Livewire\Reviews::class)->name('reviews');
    Route::get('/mais', Livewire\More::class)->name('more');
    Route::get('/perfil', Livewire\Profile::class)->name('profile');
    Route::get('/configuracoes', Livewire\Settings::class)->name('settings');
    Route::get('/boas-vindas', Livewire\Onboarding::class)->name('onboarding');
});

// Catálogo de componentes (só em desenvolvimento)
if (! app()->isProduction()) {
    Route::view('/_ui', 'ui-catalog')->middleware('auth')->name('ui');
}
