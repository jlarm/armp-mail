<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CampaignFrequency;
use App\Models\Campaign;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $campaign = $this->route('campaign');
        $listId = $campaign instanceof Campaign ? $campaign->email_list_id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'string', 'email', 'max:255'],
            'reply_to_email' => ['nullable', 'string', 'email', 'max:255'],
            'segment_id' => ['nullable', Rule::exists('segments', 'id')->where('email_list_id', $listId)],
            'template_id' => ['nullable', Rule::exists('templates', 'id')],
            'content' => ['nullable', 'array'],
            'content.*.id' => ['nullable', 'string'],
            'content.*.type' => ['required_with:content', 'string'],
            'content.*.data' => ['nullable', 'array'],
            'html' => ['nullable', 'string'],
            'track_opens' => ['boolean'],
            'track_clicks' => ['boolean'],
            'frequency' => ['nullable', Rule::enum(CampaignFrequency::class)],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
