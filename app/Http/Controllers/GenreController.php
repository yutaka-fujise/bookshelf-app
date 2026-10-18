<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function create()
    {
        return view('genres.create');
    }

    public function index()
    {
        $genres = \App\Models\Genre::withCount('books')
            ->orderBy('id')
            ->get();

        return view('genres.index', compact('genres'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('genres', 'name'),
            ],
        ]);

        \App\Models\Genre::create($validated);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    public function show(\App\Models\Genre $genre)
    {
        $books = $genre->books()
            ->with('genres')
            ->latest('books.id')
            ->paginate(12);

        return view('genres.show', compact('genre', 'books'));
    }

    public function edit(\App\Models\Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    public function update(
        \Illuminate\Http\Request $request,
        \App\Models\Genre $genre
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('genres', 'name')
                    ->ignore($genre->id),
            ],
        ]);

        $genre->update($validated);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました。');
    }

    public function destroy(\App\Models\Genre $genre)
    {
        // 書籍との紐付けを解除
        $genre->books()->detach();

        // ジャンル本体を削除
        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}
