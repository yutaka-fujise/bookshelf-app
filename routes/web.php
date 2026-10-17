<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/books/create', [BookController::class, 'create'])
    ->middleware('auth')
    ->name('books.create');

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

Route::get('/login', [LoginController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::get('/register', [RegisterController::class, 'create'])
    ->middleware('guest')
    ->name('register');

Route::post('/login', [LoginController::class, 'store'])
    ->middleware('guest');

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('/books', [BookController::class, 'store'])
    ->middleware('auth')
    ->name('books.store');

Route::post('/books/{book}/favorite', [\App\Http\Controllers\FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

Route::post('/books/{book}/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

Route::post('/reviews/{review}/like', [\App\Http\Controllers\ReviewController::class, 'like'])
    ->middleware('auth')
    ->name('reviews.like');