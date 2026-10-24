<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($book->reviews()
            ->where('user_id', $request->user()->id)
            ->exists()) {
            return back()
                ->withErrors([
                    'review' => 'この書籍にはすでにレビューを投稿しています。',
                ])
                ->withInput();
        }

        $book->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    public function like(Request $request, Review $review): RedirectResponse
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

    public function update(
        Request $request,
        Review $review
    ): RedirectResponse {
        // 投稿者本人か確認
        abort_unless(
            $request->user()->id === $review->user_id,
            403
        );

        // 入力内容を検証
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // 評価とコメントの両方が変更されていなければ更新しない
        if (
            (int) $review->rating === (int) $validated['rating']
            && $review->comment === ($validated['comment'] ?? null)
        ) {
            return redirect()
                ->route('books.show', $review->book_id)
                ->with('info', '変更内容がありません。');
        }

        // 変更がある場合だけ更新する
        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()
            ->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

    public function edit(Review $review)
    {
        // レビュー投稿者本人だけが編集可能
        abort_unless(
            auth()->id() === $review->user_id,
            403
        );

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    public function destroy(
        Request $request,
        Review $review
    ): RedirectResponse {
        // レビュー投稿者本人だけが削除できる
        abort_unless(
            $request->user()->id === $review->user_id,
            403
        );

        $bookId = $review->book_id;

        $review->delete();

        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビューを削除しました。');
    }
}
