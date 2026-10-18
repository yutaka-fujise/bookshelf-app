<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenreController;

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

Route::get('/', [BookController::class, 'index']);

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/books/create', [BookController::class, 'create'])
    ->middleware('auth')
    ->name('books.create');

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

Route::put('/books/{book}', [BookController::class, 'update'])
    ->middleware('auth')
    ->name('books.update');

Route::post('/books', [BookController::class, 'store'])
    ->middleware('auth')
    ->name('books.store');

Route::get('/books/{book}/edit', [BookController::class, 'edit'])
    ->middleware('auth')
    ->name('books.edit');

Route::post('/books/{book}/favorite', [\App\Http\Controllers\FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

Route::post('/books/{book}/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

Route::delete('/books/{book}', [BookController::class, 'destroy'])
    ->middleware('auth')
    ->name('books.destroy');

Route::post('/reviews/{review}/like', [\App\Http\Controllers\ReviewController::class, 'like'])
    ->middleware('auth')
    ->name('reviews.like');

Route::put('/reviews/{review}', [ReviewController::class, 'update'])
    ->middleware('auth')
    ->name('reviews.update');

Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
    ->middleware('auth')
    ->name('reviews.edit');

Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
    ->middleware('auth')
    ->name('reviews.destroy');

Route::get('/genres', [GenreController::class, 'index'])
    ->middleware('auth')
    ->name('genres.index');

Route::get('/genres/create', [GenreController::class, 'create'])
    ->middleware('auth')
    ->name('genres.create');

Route::get('/genres/{genre}', [GenreController::class, 'show'])
    ->middleware('auth')
    ->name('genres.show');

Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])
    ->middleware('auth')
    ->name('genres.edit');

Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])
    ->middleware('auth')
    ->name('genres.destroy');

Route::post('/genres', [GenreController::class, 'store'])
    ->middleware('auth')
    ->name('genres.store');

Route::put('/genres/{genre}', [GenreController::class, 'update'])
    ->middleware('auth')
    ->name('genres.update');
