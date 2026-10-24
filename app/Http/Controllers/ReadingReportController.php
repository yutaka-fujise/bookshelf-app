<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ReadingReportController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $reviews = Review::where('user_id', $userId);

        $stats = [
            'summary' => [
                'total_reviews' => (clone $reviews)->count(),

                'books_read' => ReadingPlan::where('user_id', $userId)
                    ->where('status', 'completed')
                    ->distinct()
                    ->count('book_id'),

                'average_rating' => (float) ((clone $reviews)->avg('rating') ?? 0),
            ],

            'rating_distribution' => collect(range(1, 5))
                ->map(function ($rating) use ($reviews) {
                    return (clone $reviews)
                        ->where('rating', $rating)
                        ->count();
                }),

            'top_rated_books' => (clone $reviews)
                ->with('book')
                ->where('rating', '>=', 4)
                ->orderByDesc('rating')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->book->id,
                        'title' => $review->book->title,
                        'author' => $review->book->author,
                        'rating' => $review->rating,
                    ];
                }),

            'genre_ratings' => Genre::query()
                ->whereHas('books.reviews', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->with(['books.reviews' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }])
                ->get()
                ->map(function ($genre) {
                    $ratings = $genre->books
                        ->flatMap(function ($book) {
                            return $book->reviews->pluck('rating');
                        });

                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'count' => $ratings->count(),
                        'average_rating' => (float) $ratings->avg(),
                    ];
                })
                ->sort(function ($a, $b) {
                    return ($b['average_rating'] <=> $a['average_rating'])
                        ?: ($b['count'] <=> $a['count'])
                        ?: ($a['id'] <=> $b['id']);
                })
                ->take(5)
                ->values(),
        ];

        return view('reports.index', compact('stats'));
    }
}
