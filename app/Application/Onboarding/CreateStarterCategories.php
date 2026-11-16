<?php

namespace App\Application\Onboarding;

use App\Domain\Categorization\RuleField;
use App\Domain\Categorization\RuleOperator;
use App\Models\User;

class CreateStarterCategories
{
    /**
     * @var array<string, array{color: string, merchants: list<string>}>
     */
    private const STARTER = [
        'Jedzenie' => ['color' => '#e07a3f', 'merchants' => ['biedronka', 'lidl', 'żabka', 'carrefour', 'auchan']],
        'Transport' => ['color' => '#3a7ca5', 'merchants' => ['orlen', 'bolt', 'uber', 'pkp', 'jakdojade']],
        'Mieszkanie' => ['color' => '#6d597a', 'merchants' => ['czynsz', 'tauron', 'pgnig', 'ikea']],
        'Rozrywka' => ['color' => '#d1495b', 'merchants' => ['netflix', 'spotify', 'kino', 'steam']],
        'Zdrowie' => ['color' => '#2a9d8f', 'merchants' => ['apteka', 'rossmann', 'luxmed', 'medicover']],
        'Restauracje' => ['color' => '#c9a227', 'merchants' => ['pyszne', 'glovo', 'wolt', 'mcdonald']],
        'Inne' => ['color' => '#64748b', 'merchants' => []],
    ];

    public function handle(User $user): void
    {
        foreach (self::STARTER as $name => $starter) {
            $category = $user->categories()->create([
                'name' => $name,
                'color' => $starter['color'],
            ]);

            foreach ($starter['merchants'] as $merchant) {
                $user->rules()->create([
                    'category_id' => $category->id,
                    'field' => RuleField::Any,
                    'operator' => RuleOperator::Contains,
                    'pattern' => $merchant,
                    'priority' => 100,
                ]);
            }
        }
    }
}
