<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GenreController extends Controller
{
    public function create()
    {
        return view('genres.create');
    }

    public function index()
    {
        $genres = Genre::withCount('books')
            ->orderBy('id')
            ->get();

        return view('genres.index', compact('genres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('genres', 'name'),
            ],
        ]);

        Genre::create($validated);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    public function show(Genre $genre)
    {
        $books = $genre->books()
            ->with('genres')
            ->latest('books.id')
            ->paginate(12);

        return view('genres.show', compact('genre', 'books'));
    }

    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('genres', 'name')
                    ->ignore($genre->id),
            ],
        ]);

        // 変更がない場合はDBを更新しない
        if ($genre->name === $validated['name']) {
            return redirect()
                ->route('genres.show', $genre)
                ->with('info', '変更内容がありません。');
        }

        // 変更がある場合だけ更新する
        $genre->update($validated);

        return redirect()
            ->route('genres.show', $genre)
            ->with('success', 'ジャンルを更新しました。');
    }

    public function destroy(Genre $genre)
    {
        // 書籍が紐付いているジャンルは削除できない
        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with(
                    'error',
                    '書籍が紐付いているジャンルは削除できません。'
                );
        }

        // 書籍が紐付いていない場合のみ削除する
        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}
