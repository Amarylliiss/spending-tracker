<?php

namespace App\Application\Export;

use App\Domain\Shared\Money;
use App\Domain\Shared\Month;
use App\Models\Expense;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseCsvExport
{
    private const HEADER = ['Data', 'Sprzedawca', 'Opis', 'Kategoria', 'Kwota', 'Przypisanie kategorii'];

    public function download(User $user, ?Month $month = null, ?int $categoryId = null): StreamedResponse
    {
        $filename = 'wydatki-'.($month?->key() ?? 'wszystkie').'.csv';

        return response()->streamDownload(function () use ($user, $month, $categoryId): void {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::HEADER, ';', escape: '');

            $user->expenses()
                ->with('category')
                ->when($month, fn ($query) => $query->inMonth($month))
                ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                ->orderBy('spent_on')
                ->orderBy('id')
                ->lazy()
                ->each(fn (Expense $expense) => fputcsv($out, $this->row($expense), ';', escape: ''));

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return list<string>
     */
    public function row(Expense $expense): array
    {
        return [
            $expense->spent_on->toDateString(),
            $this->safe($expense->merchant ?? ''),
            $this->safe($expense->description),
            $expense->category?->name ?? 'Bez kategorii',
            Money::ofCents($expense->amount)->decimal(','),
            match ($expense->categorized_by->value) {
                'manual' => 'ręcznie',
                'rule' => 'reguła',
                default => 'brak',
            },
        ];
    }

    private function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
