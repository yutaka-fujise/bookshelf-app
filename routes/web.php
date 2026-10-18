<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReviewController;
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

// 書籍（Books）

Route::get('/', [BookController::class, 'index']);

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/books/create', [BookController::class, 'create'])
    ->middleware('auth')
    ->name('books.create');

Route::post('/books', [BookController::class, 'store'])
    ->middleware('auth')
    ->name('books.store');

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

Route::get('/books/{book}/edit', [BookController::class, 'edit'])
    ->middleware('auth')
    ->name('books.edit');

Route::put('/books/{book}', [BookController::class, 'update'])
    ->middleware('auth')
    ->name('books.update');

Route::delete('/books/{book}', [BookController::class, 'destroy'])
    ->middleware('auth')
    ->name('books.destroy');

// お気に入り（Favorites）

Route::get('/favorites', [FavoriteController::class, 'index'])
    ->middleware('auth')
    ->name('favorites.index');

Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

// レビュー（Reviews）

Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
    ->middleware('auth')
    ->name('reviews.edit');

Route::put('/reviews/{review}', [ReviewController::class, 'update'])
    ->middleware('auth')
    ->name('reviews.update');

Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
    ->middleware('auth')
    ->name('reviews.destroy');

Route::post('/reviews/{review}/like', [ReviewController::class, 'like'])
    ->middleware('auth')
    ->name('reviews.like');

// ジャンル（Genres）

Route::get('/genres', [GenreController::class, 'index'])
    ->middleware('auth')
    ->name('genres.index');

Route::get('/genres/create', [GenreController::class, 'create'])
    ->middleware('auth')
    ->name('genres.create');

Route::post('/genres', [GenreController::class, 'store'])
    ->middleware('auth')
    ->name('genres.store');

Route::get('/genres/{genre}', [GenreController::class, 'show'])
    ->middleware('auth')
    ->name('genres.show');

Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])
    ->middleware('auth')
    ->name('genres.edit');

Route::put('/genres/{genre}', [GenreController::class, 'update'])
    ->middleware('auth')
    ->name('genres.update');

Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])
    ->middleware('auth')
    ->name('genres.destroy');

// 読書計画（Reading Plans）

Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
    ->middleware('auth')
    ->name('reading-plans.index');

Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
    ->middleware('auth')
    ->name('reading-plans.create');

Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
    ->middleware('auth')
    ->name('reading-plans.store');

Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])
    ->middleware('auth')
    ->name('reading-plans.edit');

Route::put('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'update'])
    ->middleware('auth')
    ->name('reading-plans.update');

Route::delete('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'destroy'])
    ->middleware('auth')
    ->name('reading-plans.destroy');

Route::patch('/reading-plans/{readingPlan}/complete', [ReadingPlanController::class, 'complete'])
    ->middleware('auth')
    ->name('reading-plans.complete');

// 通知（Notifications）

Route::get('/notifications', [NotificationController::class, 'index'])
    ->middleware('auth')
    ->name('notifications.index');

Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
    ->middleware('auth')
    ->name('notifications.read');

// ランキング（Ranking）

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');
