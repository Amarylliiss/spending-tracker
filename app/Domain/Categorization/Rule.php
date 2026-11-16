<?php

namespace App\Domain\Categorization;

final readonly class Rule
{
    public function __construct(
        public int $id,
        public int $categoryId,
        public RuleField $field,
        public RuleOperator $operator,
        public string $pattern,
        public int $priority = 100,
    ) {}

    public function matches(?string $merchant, string $description): bool
    {
        $pattern = self::normalize($this->pattern);

        if ($pattern === '') {
            return false;
        }

        $candidates = match ($this->field) {
            RuleField::Merchant => [$merchant ?? ''],
            RuleField::Description => [$description],
            RuleField::Any => [$merchant ?? '', $description],
        };

        foreach ($candidates as $candidate) {
            if ($this->operator->test(self::normalize($candidate), $pattern)) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
    }
}
