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

    public function update(
        Request $request,
        \App\Models\Review $review
    ): RedirectResponse {
        // 投稿者本人か確認
        abort_unless(
            $request->user()->id === $review->user_id,
            403
        );

        // 入力内容を検証
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        // レビューを更新
        $review->update($validated);

        return redirect()
            ->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

    public function edit(\App\Models\Review $review)
    {
        // レビューの投稿者本人だけが編集可能
        abort_unless(
            auth()->id() === $review->user_id,
            403
        );

        // 編集画面で書籍タイトルを表示するために取得
        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    public function destroy(
        Request $request,
        \App\Models\Review $review
    ): RedirectResponse {
        // レビュー投稿者本人だけが削除できる
        abort_unless(
            $request->user()->id === $review->user_id,
            403
        );

        // 削除後に戻る書籍IDを保存
        $bookId = $review->book_id;

        // レビューを削除
        $review->delete();

        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビューを削除しました。');
    }
}