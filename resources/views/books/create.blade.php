<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('書籍の登録') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <!-- ISBN検索 -->
            <div class="bg-gradient-to-br from-indigo-50 to-blue-50 border border-indigo-100 shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18 17.5 18s-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="font-semibold text-base text-gray-800">
                            ISBN から書籍情報を自動入力
                        </h3>
                    </div>

                    <p class="text-xs text-gray-600 mb-3">
                        13桁のISBNを入力すると、書籍情報を取得してフォームを自動補完します。
                    </p>

                    <div class="flex items-stretch gap-2 w-full">
                        <input
                            type="text"
                            id="isbn-search"
                            placeholder="例: 9784101010014"
                            class="w-full flex-1 min-w-0 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                            inputmode="numeric"
                            maxlength="13"
                        >

                        <button
                            type="button"
                            id="fetch-btn"
                            class="shrink-0 inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-700 disabled:bg-blue-300 text-white text-sm font-bold py-2 px-4 rounded shadow-sm transition whitespace-nowrap"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                            </svg>
                            <span id="fetch-btn-label">検索</span>
                        </button>
                    </div>

                    <p id="fetch-error" class="mt-2 text-sm text-red-600 hidden"></p>
                    <p id="fetch-success" class="mt-2 text-sm text-green-600 hidden"></p>
                </div>
            </div>

            <!-- 書籍登録フォーム -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('books.store') }}" method="POST" novalidate>
                        @include('books._form')

                        <div class="flex items-center justify-end mt-6 pt-6 border-t border-gray-200">
                            <a href="{{ route('books.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
                                キャンセル
                            </a>

                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded"
                            >
                                登録
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('fetch-btn').addEventListener('click', async function () {
            const isbn = document.getElementById('isbn-search').value.trim();
            const errorEl = document.getElementById('fetch-error');
            const successEl = document.getElementById('fetch-success');
            const labelEl = document.getElementById('fetch-btn-label');

            errorEl.classList.add('hidden');
            successEl.classList.add('hidden');
            errorEl.textContent = '';
            successEl.textContent = '';

            // 数字13桁以外はAPIへ送信しない
            if (!/^[0-9]{13}$/.test(isbn)) {
                errorEl.textContent = 'ISBN-13は数字13桁で入力してください。';
                errorEl.classList.remove('hidden');
                return;
            }

            this.disabled = true;
            labelEl.textContent = '検索中...';

            try {
                const response = await fetch(`/books/isbn/${isbn}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    if (response.status === 404) {
                        errorEl.textContent = '入力されたISBNは存在しません。';
                    } else {
                        errorEl.textContent = '書籍情報の取得に失敗しました。時間をおいて再度お試しください。';
                    }

                    errorEl.classList.remove('hidden');
                    return;
                }

                // 取得できた書籍情報をフォームへ反映
                document.getElementById('title').value = data.title ?? '';
                document.getElementById('author').value = data.author ?? '';
                document.getElementById('isbn').value = isbn;
                document.getElementById('description').value = data.description ?? '';
                document.getElementById('image_url').value = data.image_url ?? '';

                // 出版日は取得できなければ空欄にする
                const publishedDateEl = document.getElementById('published_date');
                publishedDateEl.value = '';

                if (data.published_date) {
                    const publishedDate = String(data.published_date);

                    // YYYY-MM-DD形式
                    if (/^\d{4}-\d{2}-\d{2}$/.test(publishedDate)) {
                        const date = new Date(`${publishedDate}T00:00:00Z`);

                        if (
                            !Number.isNaN(date.getTime()) &&
                            date.toISOString().slice(0, 10) === publishedDate
                        ) {
                            publishedDateEl.value = publishedDate;
                        }
                    } else {
                        publishedDateEl.value = '';
                    }
                }

                // APIカテゴリと登録済みジャンル名が完全一致したものだけ選択
                const categories = Array.isArray(data.categories)
                    ? data.categories
                    : (Array.isArray(data.genres) ? data.genres : []);

                const categoryNames = categories.filter(
                    category => typeof category === 'string'
                );

                document.querySelectorAll('input[name="genres[]"]').forEach(checkbox => {
                    const genreName = checkbox.closest('label')
                        ?.querySelector('span')
                        ?.textContent
                        ?.trim();

                    checkbox.checked = categoryNames.includes(genreName);
                });

                // 自動入力後も各項目は自由に編集できる
                successEl.textContent = '書籍情報を取得しました。';
                successEl.classList.remove('hidden');

            } catch (error) {
                errorEl.textContent = '書籍情報の取得に失敗しました。時間をおいて再度お試しください。';
                errorEl.classList.remove('hidden');
            } finally {
                this.disabled = false;
                labelEl.textContent = '検索';
            }
        });
    </script>
    @endpush
</x-app-layout>
