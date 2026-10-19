<?php

namespace App\Http\Controllers;

use App\Models\Book;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->when(request('keyword'), function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when(request('genre'), function ($query, $genre) {
                $query->whereHas('genres', function ($query) use ($genre) {
                    $query->where('genres.id', $genre);
                });
            })
            ->when(request('sort', 'newest') === 'newest', function ($query) {
                $query->orderByDesc('created_at')
                    ->orderByDesc('id');
            })
            ->when(request('sort') === 'oldest', function ($query) {
                $query->orderBy('created_at')
                    ->orderBy('id');
            })
            ->when(request('sort') === 'rating', function ($query) {
                $query->orderByDesc('reviews_avg_rating')
                    ->orderBy('id');
            })
            ->when(request('sort') === 'title', function ($query) {
                $query->orderBy('title')
                    ->orderBy('id');
            })
            ->paginate(10)
            ->withQueryString();

        $genres = \App\Models\Genre::orderBy('id')->get();

        return view('books.index', compact('books', 'genres'));
    }

    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedUsers',
        ]);

        return view('books.show', compact('book'));
    }

    public function create()
    {
        $genres = \App\Models\Genre::orderBy('id')->get();

        return view('books.create', compact('genres'));
    }

    public function store(\App\Http\Requests\StoreBookRequest $request)
    {
        $validated = $request->validated();

        $book = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $book = \App\Models\Book::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn13' => $validated['isbn'],
                'published_at' => $validated['published_date'],
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function update(
        \App\Http\Requests\UpdateBookRequest $request,
        Book $book
    ) {
        $validated = $request->validated();

        \Illuminate\Support\Facades\DB::transaction(function () use ($book, $validated) {
            $book->update([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn13' => $validated['isbn'],
                'published_at' => $validated['published_date'],
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    public function edit(Book $book)
    {
        abort_unless(
            auth()->id() === $book->user_id,
            403
        );

        $book->load('genres');

        $genres = \App\Models\Genre::orderBy('id')->get();

        return view('books.edit', compact('book', 'genres'));
    }

    public function destroy(Book $book)
    {
        abort_unless(
            auth()->id() === $book->user_id,
            403
        );

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
