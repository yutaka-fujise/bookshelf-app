<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            読書計画
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <form method="GET" action="{{ route('reading-plans.index') }}"
                      class="flex items-center gap-2">
                    <label for="status" class="text-sm text-gray-600">
                        状態:
                    </label>
                    <select id="status" name="status"
                            onchange="this.form.submit()"
                            class="rounded-md border-gray-300 text-sm">
                        <option value="" @selected(request('status') === null || request('status') === '')>
                            すべて
                        </option>
                        <option value="in_progress" @selected(request('status') === 'in_progress')>
                            進行中
                        </option>
                        <option value="expired" @selected(request('status') === 'expired')>
                            期限切れ
                        </option>
                        <option value="completed" @selected(request('status') === 'completed')>
                            読了済み
                        </option>
                    </select>
                </form>

                <a href="{{ route('reading-plans.create') }}"
                   class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded">
                    新規計画作成
                </a>
            </div>

            @if(session('success'))
                <div class="bg-green-50 text-green-700 p-4 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($plans->isEmpty())
                <div class="bg-white rounded-lg p-5 text-sm text-gray-500">
                    該当する読書計画はありません。
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 text-left">書籍名</th>
                                <th class="p-4 text-left">目標日</th>
                                <th class="p-4 text-left">状態</th>
                                <th class="p-4 text-left">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                                <tr class="border-t">
                                    <td class="p-4">
                                        <a href="{{ route('books.show', $plan->book) }}"
                                           class="text-blue-600 hover:underline">
                                            {{ $plan->book->title }}
                                        </a>
                                    </td>
                                    <td class="p-4">
                                        {{ $plan->target_date->format('Y/m/d') }}
                                    </td>
                                    <td class="p-4">
                                        @switch($plan->status)
                                            @case('in_progress')
                                                進行中
                                                @break
                                            @case('expired')
                                                期限切れ
                                                @break
                                            @case('completed')
                                                読了済み
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            @if($plan->status !== 'completed')
                                                <a href="{{ route('reading-plans.edit', $plan) }}"
                                                   class="text-blue-600 hover:underline">
                                                    編集
                                                </a>

                                                <form method="POST"
                                                      action="{{ route('reading-plans.complete', $plan) }}"
                                                      onsubmit="return confirm('この読書計画を読了済みにしますか？');">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit"
                                                            class="text-green-600 hover:underline">
                                                        読了
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST"
                                                  action="{{ route('reading-plans.destroy', $plan) }}"
                                                  onsubmit="return confirm('この読書計画を削除しますか？');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                        class="text-red-600 hover:underline">
                                                    削除
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $plans->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>