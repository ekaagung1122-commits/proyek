<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotMessage;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(private readonly ChatbotService $chatbotService)
    {
    }

    public function index(Request $request)
    {
        $messages = ChatbotMessage::where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->limit(100)
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'message' => 'Riwayat chatbot',
            'data' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();
        $content = trim($validated['message']);

        if ($content === '') {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => [
                    'message' => ['Pesan tidak boleh kosong.'],
                ],
            ], 422);
        }

        $userMessage = $this->chatbotService->storeUserMessage($user, $content);
        $assistantMessage = $this->chatbotService->reply($user, $content);

        return response()->json([
            'message' => 'Pesan berhasil diproses',
            'data' => [
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => $userMessage->role,
                    'content' => $userMessage->content,
                    'created_at' => $userMessage->created_at,
                ],
                'assistant_message' => [
                    'id' => $assistantMessage->id,
                    'role' => $assistantMessage->role,
                    'content' => $assistantMessage->content,
                    'created_at' => $assistantMessage->created_at,
                ],
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        ChatbotMessage::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Riwayat chatbot berhasil dihapus',
        ]);
    }
}
