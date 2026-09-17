<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\StoreChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Owner-only AI chatbot endpoint for the Flutter "Store Assistant" screen.
 *
 * POST /api/assistant/ask — route middleware is role:admin, so cashiers are
 * rejected before the controller runs. The Groq key stays server-side; the
 * answer is grounded exclusively in the caller's store data (StoreChatService).
 */
final class StoreChatController extends Controller
{
    public function __construct(private readonly StoreChatService $assistant) {}

    public function ask(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:500'],

            // Optional earlier turns so follow-ups keep context. Capped and
            // role-restricted — they are DATA for the model, never commands
            // (the system prompt in StoreChatService says so explicitly).
            'history' => ['nullable', 'array', 'max:8'],
            'history.*.role' => ['required_with:history.*', 'string', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required_with:history.*', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->assistant->ask(
                $request->user(),
                trim($payload['question']),
                array_map(
                    fn (array $turn): array => [
                        'role' => (string) $turn['role'],
                        'content' => (string) $turn['content'],
                    ],
                    $payload['history'] ?? [],
                ),
            );
        } catch (OrderPlacementException $exception) {
            $code = $exception->getCode();

            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $code,
            ], $code >= 400 && $code < 600 ? $code : 502);
        }

        return response()->json([
            'reply' => $result['answer'],
            'model' => $result['model'],
            'store_id' => $result['store_id'],
            'asked_at' => now()->toIso8601String(),
        ]);
    }
}
