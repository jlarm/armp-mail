<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('email_lists', 'slug')->ignore($this->route('list')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_from_name' => ['required', 'string', 'max:255'],
            'default_from_email' => ['required', 'string', 'email', 'max:255'],
            'default_reply_to_email' => ['nullable', 'string', 'email', 'max:255'],
            'requires_confirmation' => ['boolean'],
            'redirect_after_subscribed' => ['nullable', 'string', 'url', 'max:255'],
            'redirect_after_unsubscribed' => ['nullable', 'string', 'url', 'max:255'],
            'campaign_mails_per_minute' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
