<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'genre_id' => 'sometimes|integer|min:1',
            'keyword' => 'sometimes|nullable|string|max:255',
        ]);

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->keyword, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($request->genre_id, function ($query, $genreId) {
                $query->whereHas('genres', function ($query) use ($genreId) {
                    $query->where('genres.id', $genreId);
                });
            })
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $books->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'image_url' => $book->image_url,
                    'genres' => $book->genres->map(function ($genre) {
                        return [
                            'id' => $genre->id,
                            'name' => $genre->name,
                        ];
                    }),
                    'average_rating' => $book->reviews_avg_rating !== null
                        ? (float) $book->reviews_avg_rating
                        : null,
                    'review_count' => $book->reviews_count,
                ];
            }),
            'current_page' => $books->currentPage(),
            'last_page' => $books->lastPage(),
            'per_page' => $books->perPage(),
            'total' => $books->total(),
        ]);
    }

    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user:id,name']);

        return response()->json([
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'isbn13' => $book->isbn13,
                'published_at' => $book->published_at,
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => $book->genres->map(function ($genre) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                    ];
                }),
                'reviews' => $book->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'user' => [
                            'id' => $review->user?->id,
                            'name' => $review->user?->name,
                        ],
                    ];
                }),
            ],
        ]);
    }

    public function store(\App\Http\Requests\StoreBookRequest $request)
    {
        $validated = $request->validated();

        $book = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $book = Book::create([
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

        $book->load('genres');

        return response()->json([
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'isbn13' => $book->isbn13,
                'published_at' => $book->published_at,
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => $book->genres->map(function ($genre) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                    ];
                }),
            ],
        ], 201);
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

        $book->load('genres');

        return response()->json([
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'isbn13' => $book->isbn13,
                'published_at' => $book->published_at,
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => $book->genres->map(function ($genre) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                    ];
                }),
            ],
        ]);
    }

    public function destroy(\Illuminate\Http\Request $request, Book $book)
    {
        Gate::authorize('delete', $book);

        $book->delete();

        return response()->json([
            'message' => '書籍を削除しました。',
        ], 200);
    }
}
