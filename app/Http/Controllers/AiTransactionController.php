<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiTransactionController extends Controller
{
    public function __construct(
        protected AiParserService $aiParserService
    ) {}

    /**
     * Parse natural language transaction input.
     */
    public function parse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $result = $this->aiParserService->parse(
            $validated['message'],
            $request->user(),
            'web'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'result' => $result['result']->toArray(),
                'log_id' => $result['log_id'],
                'matched_category_id' => $result['matched_category_id'],
                'matched_account_id' => $result['matched_account_id'],
                'matched_from_account_id' => $result['matched_from_account_id'],
                'matched_to_account_id' => $result['matched_to_account_id'],
            ],
        ]);
    }

    /**
     * Confirm parsed transaction data and save into transactions.
     */
    public function confirm(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'account_id' => [
                Rule::requiredIf(fn () => in_array($request->input('type'), ['income', 'expense'])),
                'nullable',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
            'from_account_id' => [
                Rule::requiredIf(fn () => $request->input('type') === 'transfer'),
                'nullable',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
            'to_account_id' => [
                Rule::requiredIf(fn () => $request->input('type') === 'transfer'),
                'nullable',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
                'different:from_account_id',
            ],
            'log_id' => ['nullable', 'uuid'],
        ]);

        $transaction = $this->aiParserService->confirm(
            $request->user(),
            $validated,
            $request->input('log_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaction successfully created via AI!',
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Reject an AI parsing result.
     */
    public function reject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_id' => ['required', 'uuid'],
        ]);

        $this->aiParserService->reject($request->user(), $validated['log_id']);

        return response()->json([
            'success' => true,
            'message' => 'AI parsing rejected.',
        ]);
    }
}
