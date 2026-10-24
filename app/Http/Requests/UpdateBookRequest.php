<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $book = $this->route('book');

        return $this->user() !== null
            && $book !== null
            && Gate::allows('update', $book);
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:120'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'digits:13',
                Rule::unique('books', 'isbn13')->ignore($book->id),
            ],
            'published_date' => [
                $this->is('api/v1/books/*') ? 'required' : 'nullable',
                'date',
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'distinct', Rule::exists('genres', 'id')],
        ];
    }
}
