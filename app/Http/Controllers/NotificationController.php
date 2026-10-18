<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // ログインユーザー本人の通知だけを取得
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function read(\App\Models\Notification $notification)
    {
        // 自分宛ての通知だけ既読にできる
        abort_unless(
            $notification->user_id === auth()->id(),
            403
        );

        // 未読の場合だけ既読日時を記録する
        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
