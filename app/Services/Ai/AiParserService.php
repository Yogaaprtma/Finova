<?php

namespace App\Services\Ai;

use App\Models\Account;
use App\Models\AiParsingLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\Adapters\GeminiAdapter;
use App\Services\Ai\Adapters\LocalRegexFallbackAdapter;
use App\Services\Ai\Adapters\OpenAiAdapter;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AiParserService
{
    protected AiProviderInterface $provider;
    protected TransactionService $transactionService;

    public function __construct(
        ?AiProviderInterface $provider = null,
        ?TransactionService $transactionService = null
    ) {
        $this->transactionService = $transactionService ?? app(TransactionService::class);
        $this->provider = $provider ?? $this->resolveProvider();
    }

    /**
     * Resolve the configured AI provider, falling back to local regex engine if unconfigured.
     */
    protected function resolveProvider(): AiProviderInterface
    {
        $default = config('finance.ai.default_provider', 'gemini');

        if ($default === 'gemini') {
            $adapter = new GeminiAdapter();
            if ($adapter->isAvailable()) {
                return $adapter;
            }
        } elseif ($default === 'openai') {
            $adapter = new OpenAiAdapter();
            if ($adapter->isAvailable()) {
                return $adapter;
            }
        }

        // Fallback
        return new LocalRegexFallbackAdapter();
    }

    /**
     * Parse message with user context, log the attempt, and match accounts & categories.
     *
     * @return array{result: AiParseResult, log_id: string, matched_category_id: ?string, matched_account_id: ?string, matched_from_account_id: ?string, matched_to_account_id: ?string}
     */
    public function parse(string $message, User $user, string $source = 'web'): array
    {
        $categories = Category::where('user_id', $user->id)
            ->select('id', 'name', 'type', 'icon', 'color')
            ->get();

        $accounts = Account::where('user_id', $user->id)
            ->select('id', 'name', 'type')
            ->get();

        $context = [
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => Str::slug($c->name),
                'type' => $c->type,
            ])->toArray(),
            'accounts' => $accounts->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
            ])->toArray(),
        ];

        $parseResult = $this->provider->parseTransaction($message, $context);

        // Match category ID
        $matchedCategoryId = null;
        if ($parseResult->categorySlug) {
            $catSlug = Str::slug($parseResult->categorySlug);
            $matched = $categories->first(fn ($c) => Str::slug($c->name) === $catSlug || mb_stripos($c->name, $parseResult->categorySlug) !== false);
            if ($matched) {
                $matchedCategoryId = $matched->id;
            }
        }

        // Match account ID
        $matchedAccountId = null;
        if ($parseResult->accountHint) {
            $hint = mb_strtolower($parseResult->accountHint);
            $matched = $accounts->first(fn ($a) => mb_stripos(mb_strtolower($a->name), $hint) !== false);
            if ($matched) {
                $matchedAccountId = $matched->id;
            }
        }
        if (!$matchedAccountId && $accounts->isNotEmpty()) {
            $matchedAccountId = $user->default_account_id ?? $accounts->first()->id;
        }

        // Match Transfer from/to accounts
        $matchedFromAccountId = null;
        $matchedToAccountId = null;
        if ($parseResult->transactionType === 'transfer') {
            if ($parseResult->fromAccountHint) {
                $fHint = mb_strtolower($parseResult->fromAccountHint);
                $fromAcc = $accounts->first(fn ($a) => mb_stripos(mb_strtolower($a->name), $fHint) !== false);
                $matchedFromAccountId = $fromAcc?->id;
            }
            if ($parseResult->toAccountHint) {
                $tHint = mb_strtolower($parseResult->toAccountHint);
                $toAcc = $accounts->first(fn ($a) => mb_stripos(mb_strtolower($a->name), $tHint) !== false);
                $matchedToAccountId = $toAcc?->id;
            }
            if (!$matchedFromAccountId && $accounts->isNotEmpty()) {
                $matchedFromAccountId = $user->default_account_id ?? $accounts->first()->id;
            }
            if (!$matchedToAccountId && $accounts->count() > 1) {
                $other = $accounts->first(fn ($a) => $a->id !== $matchedFromAccountId);
                $matchedToAccountId = $other?->id;
            }
        }

        // Persist to ai_parsing_logs
        $log = AiParsingLog::create([
            'user_id' => $user->id,
            'source' => $source,
            'raw_message' => $message,
            'parsed_data' => $parseResult->toArray(),
            'confidence' => $parseResult->confidence,
            'ai_provider' => $parseResult->provider,
            'ai_model' => $parseResult->model,
            'prompt_version' => $parseResult->promptVersion,
            'status' => 'pending',
            'response_time_ms' => $parseResult->responseTimeMs,
        ]);

        return [
            'result' => $parseResult,
            'log_id' => $log->id,
            'matched_category_id' => $matchedCategoryId,
            'matched_account_id' => $matchedAccountId,
            'matched_from_account_id' => $matchedFromAccountId,
            'matched_to_account_id' => $matchedToAccountId,
        ];
    }

    /**
     * Confirm parsed transaction data and save into real financial database.
     *
     * @param array<string, mixed> $data
     */
    public function confirm(User $user, array $data, ?string $logId = null): Transaction
    {
        $type = strtolower($data['type'] ?? 'expense');
        $amount = (int) ($data['amount'] ?? 0);
        $date = $data['date'] ?? now()->format('Y-m-d');
        $description = $data['description'] ?? 'Transaction via AI';
        $notes = $data['notes'] ?? null;
        $source = $data['source'] ?? 'ai_web';

        if ($amount <= 0) {
            throw new InvalidArgumentException('Transaction amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $type, $amount, $date, $description, $notes, $source, $data, $logId) {
            $transaction = null;

            if ($type === 'income') {
                $transaction = $this->transactionService->createIncome([
                    'user_id' => $user->id,
                    'account_id' => $data['account_id'],
                    'category_id' => $data['category_id'] ?? null,
                    'amount' => $amount,
                    'date' => $date,
                    'description' => $description,
                    'notes' => $notes,
                    'source' => $source,
                ]);
            } elseif ($type === 'transfer') {
                $transaction = $this->transactionService->createTransfer([
                    'user_id' => $user->id,
                    'from_account_id' => $data['from_account_id'],
                    'to_account_id' => $data['to_account_id'],
                    'amount' => $amount,
                    'date' => $date,
                    'description' => $description,
                    'notes' => $notes,
                    'source' => $source,
                ]);
            } else {
                // Expense (default)
                $transaction = $this->transactionService->createExpense([
                    'user_id' => $user->id,
                    'account_id' => $data['account_id'],
                    'category_id' => $data['category_id'] ?? null,
                    'amount' => $amount,
                    'date' => $date,
                    'description' => $description,
                    'notes' => $notes,
                    'source' => $source,
                ]);
            }

            if ($logId) {
                AiParsingLog::where('id', $logId)
                    ->where('user_id', $user->id)
                    ->update([
                        'status' => 'confirmed',
                        'transaction_id' => $transaction->id,
                    ]);
            }

            return $transaction;
        });
    }

    /**
     * Reject an AI parsing result.
     */
    public function reject(User $user, string $logId): void
    {
        AiParsingLog::where('id', $logId)
            ->where('user_id', $user->id)
            ->update([
                'status' => 'rejected',
            ]);
    }
}
