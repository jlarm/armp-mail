<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('subscribers', 'email')->ignore($this->route('subscriber')),
            ],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:255'],
            'attributes' => ['array'],
            'attributes.*.key' => ['nullable', 'string', 'max:255'],
            'attributes.*.value' => ['nullable', 'string'],
        ];
    }
}
