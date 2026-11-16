<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('categories')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('category')),
            ],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'monthly_limit' => ['nullable', 'regex:/^\s*\d{1,9}([.,]\d{1,2})?\s*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Masz już kategorię o tej nazwie.',
            'monthly_limit.regex' => 'Podaj limit, np. 1200.',
        ];
    }
}
