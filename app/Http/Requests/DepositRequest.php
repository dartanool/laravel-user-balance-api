<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    /**
     * Определяет, разрешено ли пользователю выполнять этот запрос.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получает правила валидации, которые применяются к этому запросу.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string',
        ];
    }
}
