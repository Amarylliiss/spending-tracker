<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'regex:/^\s*\d{1,9}([.,]\d{1,2})?\s*$/', 'not_regex:/^\s*0+([.,]0+)?\s*$/'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:255'],
            'merchant' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.regex' => 'Podaj kwotę, np. 24,99.',
            'amount.not_regex' => 'Kwota musi być większa od zera.',
            'spent_on.before_or_equal' => 'Data nie może być z przyszłości.',
        ];
    }
}
