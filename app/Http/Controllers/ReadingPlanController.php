<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use Illuminate\Http\Request;

class ReadingPlanController extends Controller
{
    public function store(StoreReadingPlanRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        // 同じユーザー・書籍に進行中の計画があるか確認
        $hasActivePlan = \App\Models\ReadingPlan::where('user_id', $user->id)
            ->where('book_id', $validated['book_id'])
            ->where('status', 'in_progress')
            ->exists();

        if ($hasActivePlan) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'book_id' => 'この書籍には既に進行中の読書計画があります。',
            ]);
        }

        // 所有者・ステータスはサーバー側で設定する
        \App\Models\ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\ReadingPlan::with('book')
            ->where('user_id', $request->user()->id);

        // 指定された状態で絞り込む
        $status = $request->query('status');

        if (in_array($status, ['in_progress', 'expired', 'completed'], true)) {
            $query->where('status', $status);
        }

        $plans = $query
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('reading-plans.index', compact('plans'));
    }

    public function create()
    {
        $books = \App\Models\Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    public function edit(\App\Models\ReadingPlan $readingPlan)
    {
        // 所有者以外は編集できない
        abort_unless(
            $readingPlan->user_id === auth()->id(),
            403
        );

        // 読了済みの計画は編集できない
        abort_if($readingPlan->status === 'completed', 403);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(
        UpdateReadingPlanRequest $request,
        \App\Models\ReadingPlan $readingPlan
    ) {
        // 所有者以外は更新できない
        abort_unless($readingPlan->user_id === $request->user()->id, 403);

        // 読了済みの計画は更新できない
        abort_if($readingPlan->status === 'completed', 403);

        // 変更できる項目は目標日だけ
        $validated = $request->validated();

        // 期限切れから復帰する場合、進行中計画との重複を防ぐ
        if ($readingPlan->status === 'expired') {
            $hasActivePlan = \App\Models\ReadingPlan::where(
                'user_id',
                $readingPlan->user_id
            )
                ->where('book_id', $readingPlan->book_id)
                ->where('status', 'in_progress')
                ->whereKeyNot($readingPlan->id)
                ->exists();

            if ($hasActivePlan) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'target_date' => 'この書籍には既に進行中の読書計画があります。',
                ]);
            }
        }

        $readingPlan->update([
            'target_date' => $validated['target_date'],
            'status' => 'in_progress',
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    public function destroy(\App\Models\ReadingPlan $readingPlan)
    {
        // 所有者以外は削除できない
        abort_unless(
            $readingPlan->user_id === auth()->id(),
            403
        );

        // 状態に関係なく削除可能
        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    public function complete(\App\Models\ReadingPlan $readingPlan)
    {
        // 所有者以外は読了処理を実行できない
        abort_unless(
            $readingPlan->user_id === auth()->id(),
            403
        );

        // 読了済みの計画は再度読了できない
        abort_if($readingPlan->status === 'completed', 403);

        // 読了状態と読了日時を記録する
        $readingPlan->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了済みにしました。');
    }
}
