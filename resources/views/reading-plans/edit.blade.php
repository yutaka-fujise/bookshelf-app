<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            読書計画編集
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm p-6">

                <!-- 対象書籍 -->
                <div class="text-sm text-gray-700">
                    対象書籍:
                    <span class="font-semibold">
                        {{ $readingPlan->book->title }}
                    </span>
                </div>

                <!-- 現在の状態 -->
                <div class="mt-1 mb-4 text-sm text-gray-700">
                    現在の状態:
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">
                        {{ match($readingPlan->status) {
                            'in_progress' => '進行中',
                            'expired' => '期限切れ',
                            'completed' => '読了済み',
                            default => $readingPlan->status,
                        } }}
                    </span>
                </div>

                <form method="POST"
                      action="{{ route('reading-plans.update', $readingPlan) }}">
                    @csrf
                    @method('PUT')

                    <!-- 期日 -->
                    <div class="mb-4">
                        <label for="target_date"
                               class="block text-sm font-medium text-gray-700">
                            期日 <span class="text-red-500">*</span>
                        </label>

                        <input type="date"
                               id="target_date"
                               name="target_date"
                               value="{{ old('target_date', $readingPlan->target_date->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                        @error('target_date')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- 操作ボタン -->
                    <div class="flex justify-end items-center gap-3">
                        <a href="{{ route('reading-plans.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900">
                            キャンセル
                        </a>

                        <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded">
                            更新
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
