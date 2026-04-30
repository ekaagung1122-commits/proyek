<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        return response()->json([
            'message' => 'Daftar Notifikasi',
            'data' => $notifications
        ]);
    }

    public function read(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        if ($notification->read_at) {
            return response()->json([
                'message' => 'Notifikasi sudah dibaca'
            ], 400);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }

    public function unread(Request $request, $id)
    {
        $notification = $request->user()->notifications()
            ->where('id', $id)
            ->firstOrFail();

        if (!$notification->read_at) {
            return response()->json([
                'message' => 'Notifikasi sudah belum dibaca'
            ], 400);
        }

        $notification->update([
            'read_at' => null
        ]);

        return response()->json([
            'message' => 'Notifikasi berhasil ditandai sebagai belum dibaca'
        ]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notifikasi berhasil dihapus'
        ]);
    }

    public function clearAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'message' => 'Semua notifikasi berhasil dihapus'
        ]);
    }
}
