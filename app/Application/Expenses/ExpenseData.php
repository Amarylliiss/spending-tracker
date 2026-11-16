<?php

namespace App\Application\Expenses;

use App\Domain\Shared\Money;
use Carbon\CarbonImmutable;

final readonly class ExpenseData
{
    public function __construct(
        public Money $amount,
        public CarbonImmutable $spentOn,
        public string $description,
        public ?string $merchant = null,
        public ?int $categoryId = null,
    ) {}

    /**
     * @param  array{amount: string|int|float, spent_on: string, description: string, merchant?: ?string, category_id?: ?int}  $input
     */
    public static function fromArray(array $input): self
    {
        $merchant = trim((string) ($input['merchant'] ?? ''));

        return new self(
            amount: Money::parse($input['amount']),
            spentOn: CarbonImmutable::parse($input['spent_on'])->startOfDay(),
            description: trim($input['description']),
            merchant: $merchant === '' ? null : $merchant,
            categoryId: isset($input['category_id']) ? (int) $input['category_id'] : null,
        );
    }
}
