<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        if ($book->reviews()
            ->where('user_id', $request->user()->id)
            ->exists()) {
            return back()
                ->withErrors(['review' => 'この書籍にはすでにレビューを投稿しています。'])
                ->withInput();
        }

        $book->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    public function like(Request $request, \App\Models\Review $review): RedirectResponse
    {
        $user = $request->user();

        if ($user->likedReviews()->whereKey($review->id)->exists()) {
            // いいね済みなら解除
            $user->likedReviews()->detach($review->id);
        } else {
            // 未登録なら登録
            $user->likedReviews()->attach($review->id);
        }

        return redirect()->route('books.show', $review->book_id);
    }
}