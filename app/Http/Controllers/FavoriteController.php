<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        if ($user->favoriteBooks()->whereKey($book->id)->exists()) {
            // 登録済みなら解除
            $user->favoriteBooks()->detach($book->id);
        } else {
            // 未登録なら追加
            $user->favoriteBooks()->attach($book->id);
        }

        return redirect()->route('books.show', $book);
    }

    public function index(Request $request)
    {
        $books = $request->user()
            ->favoriteBooks()
            ->orderBy('books.id', 'desc')
            ->paginate(12);

        return view('favorites.index', compact('books'));
    }
}
