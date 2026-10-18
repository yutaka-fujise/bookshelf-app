<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            新規読書計画作成
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm p-6">

                <form method="POST"
                      action="{{ route('reading-plans.store') }}"
                      novalidate>
                    @csrf

                    <!-- 書籍 -->
                    <div class="mb-4">
                        <label for="book_id"
                               class="block text-sm font-medium text-gray-700">
                            書籍 <span class="text-red-500">*</span>
                        </label>

                        <select id="book_id"
                                name="book_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- 書籍を選択 --</option>

                            @foreach($books as $book)
                                <option value="{{ $book->id }}"
                                    @selected(old('book_id') == $book->id)>
                                    {{ $book->title }}
                                </option>
                            @endforeach
                        </select>

                        @error('book_id')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- 期日 -->
                    <div class="mb-4">
                        <label for="target_date"
                               class="block text-sm font-medium text-gray-700">
                            期日 <span class="text-red-500">*</span>
                        </label>

                        <input type="date"
                               id="target_date"
                               name="target_date"
                               value="{{ old('target_date') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                        @error('target_date')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-end items-center gap-3">
                        <a href="{{ route('reading-plans.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900">
                            キャンセル
                        </a>

                        <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded">
                            登録
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
