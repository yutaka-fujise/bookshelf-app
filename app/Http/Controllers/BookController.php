<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    public function searchByIsbn(string $isbn)
    {
        // Google Books APIから書籍情報を取得
        try {
            $response = Http::timeout(15)->get(
                'https://www.googleapis.com/books/v1/volumes',
                [
                    'q' => 'isbn:' . $isbn,
                    'key' => config('services.google_books.key'),
                ]
            );

            $googleBooksFailed = $response->failed();

            $book = $googleBooksFailed
                ? null
                : $response->json('items.0.volumeInfo');

            if ($book) {
                return response()->json([
                    'title' => $book['title'] ?? '',
                    'author' => implode(', ', $book['authors'] ?? []),
                    'description' => $book['description'] ?? '',
                    'published_date' => $book['publishedDate'] ?? '',
                    'image_url' => $book['imageLinks']['thumbnail'] ?? '',
                    'categories' => $book['categories'] ?? [],
                ]);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Google Booksへの接続に失敗してもOpen Libraryを試す
            $googleBooksFailed = true;
        }

        // Google Booksで取得できなければOpen Libraryを試す
        try {
            $openLibraryResponse = Http::timeout(15)
                ->get("https://openlibrary.org/isbn/{$isbn}.json");

            if ($openLibraryResponse->successful()) {
                $openLibraryBook = $openLibraryResponse->json();

                // 有効な書籍データが返された場合のみ採用する
                if (
                    is_array($openLibraryBook)
                    && !empty($openLibraryBook['title'])
                ) {
                    $author = '';

                    if (!empty($openLibraryBook['authors'][0]['key'])) {
                        try {
                            $authorResponse = Http::timeout(15)
                                ->get(
                                    'https://openlibrary.org'
                                    . $openLibraryBook['authors'][0]['key']
                                    . '.json'
                                );

                            if ($authorResponse->successful()) {
                                $author = $authorResponse->json('name') ?? '';
                            }
                        } catch (\Illuminate\Http\Client\ConnectionException $e) {
                            // 著者情報だけ取得できない場合は空欄にする
                        }
                    }

                    $description = $openLibraryBook['description'] ?? '';

                    if (is_array($description)) {
                        $description = $description['value'] ?? '';
                    }

                    return response()->json([
                        'title' => $openLibraryBook['title'],
                        'author' => $author,
                        'description' => $description,
                        'published_date' => $openLibraryBook['publish_date'] ?? '',
                        'image_url' => !empty($openLibraryBook['covers'][0])
                            ? 'https://covers.openlibrary.org/b/id/'
                                . $openLibraryBook['covers'][0]
                                . '-M.jpg'
                            : '',
                        'categories' => [],
                    ]);
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Open Libraryへの接続にも失敗した場合は下でエラーを返す
        }

        // Google Books自体が失敗し、代替取得も失敗した場合
        if ($googleBooksFailed) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 502);
        }

        // Google Booksでは検索できたが、どちらにも書籍がない場合
        return response()->json([
            'error' => '書籍が見つかりませんでした。',
        ], 404);
    }

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

        $book->load('genres');

        $fields = [
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn13' => $validated['isbn'],
            'published_at' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ];

        $bookChanged = collect($fields)->contains(
            fn ($value, $key) => $book->{$key} instanceof \Carbon\CarbonInterface
                ? $book->{$key}->format('Y-m-d') !== $value
                : $book->{$key} !== $value
        );

        $currentGenres = $book->genres->pluck('id')->sort()->values()->all();
        $submittedGenres = collect($validated['genres'])->sort()->values()->all();

        if (!$bookChanged && $currentGenres === $submittedGenres) {
            return redirect()
                ->route('books.show', $book)
                ->with('info', '変更内容がありません。');
        }

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
