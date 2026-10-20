<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

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
}
