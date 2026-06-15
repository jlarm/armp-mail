<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\EmailList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Ensure the email is not already subscribed to this list.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $list = $this->route('list');
            $email = $this->string('email')->value();

            if ($list instanceof EmailList && $email !== '' && $list->subscribers()->where('email', $email)->exists()) {
                $validator->errors()->add('email', __('This email is already subscribed to this list.'));
            }
        });
    }
}
