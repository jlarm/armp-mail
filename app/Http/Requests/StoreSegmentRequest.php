<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\EmailList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSegmentRequest extends FormRequest
{
    private const TYPES = [
        'tags',
        'email',
        'attribute',
        'subscribed_at',
        'not_in_list',
        'received_campaign',
        'opened_campaign',
        'clicked_campaign',
        'opened_automation_mail',
        'clicked_automation_mail',
        'engagement',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $list = $this->route('list');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('segments', 'name')->where(
                    'email_list_id',
                    $list instanceof EmailList ? $list->id : null,
                )->ignore($this->route('segment')),
            ],
            'match' => ['required', 'string', 'in:all,any'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*.type' => ['required', 'string', Rule::in(self::TYPES)],
            'conditions.*.comparison' => ['nullable', 'string'],
            'conditions.*.attribute' => ['nullable', 'string', 'max:255'],
            'conditions.*.value' => ['nullable'],
        ];
    }

    /**
     * Ensure each condition carries the value its type needs.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('conditions', []) as $index => $condition) {
                $type = $condition['type'] ?? null;
                $value = $condition['value'] ?? null;

                $requireValue = fn () => (is_array($value) ? $value !== [] : (string) $value !== '')
                    || $validator->errors()->add("conditions.{$index}.value", 'This condition needs a value.');

                match ($type) {
                    'tags' => is_array($value) && $value !== []
                        ? null
                        : $validator->errors()->add("conditions.{$index}.value", 'Select at least one tag.'),
                    'attribute' => ($condition['attribute'] ?? '') !== ''
                        ? $requireValue()
                        : $validator->errors()->add("conditions.{$index}.attribute", 'Choose an attribute.'),
                    null => null,
                    default => $requireValue(),
                };
            }
        });
    }
}
