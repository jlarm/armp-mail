<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SendFeedbackType;
use App\Enums\Status;
use App\Models\CampaignDispatch;
use App\Models\Send;
use App\Models\SendFeedback;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MailgunWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        if (! $this->verifySignature($request)) {
            abort(403, 'Invalid webhook signature.');
        }

        $eventData = $request->input('event-data', []);
        $event = (string) ($eventData['event'] ?? '');

        match ($event) {
            'permanent_fail' => $this->handleBounce($eventData),
            'complained' => $this->handleComplaint($eventData),
            default => null,
        };

        return response('', 200);
    }

    private function handleBounce(array $eventData): void
    {
        $send = $this->resolveSend($eventData);

        if ($send === null || $send->bounced_at !== null) {
            return;
        }

        $send->forceFill(['bounced_at' => now()])->save();

        $dispatch = $send->sendable;

        if ($dispatch instanceof CampaignDispatch) {
            $dispatch->increment('bounce_count');
        }

        SendFeedback::create([
            'send_id' => $send->id,
            'type' => SendFeedbackType::BOUNCE->value,
            'url' => null,
            'user_agent' => null,
            'ip_address' => null,
            'happened_at' => now(),
        ]);
    }

    private function handleComplaint(array $eventData): void
    {
        $send = $this->resolveSend($eventData);

        if ($send === null || $send->complained_at !== null) {
            return;
        }

        $send->forceFill(['complained_at' => now()])->save();

        $dispatch = $send->sendable;

        if ($dispatch instanceof CampaignDispatch) {
            $dispatch->increment('unsubscribe_count');

            $list = $dispatch->campaign?->emailList;
            $subscriber = $send->subscriber;

            if ($list && $subscriber) {
                $list->subscribers()->updateExistingPivot($subscriber->id, [
                    'status' => Status::UNSUBSCRIBED->value,
                    'unsubscribed_at' => now(),
                ]);
            }
        }

        SendFeedback::create([
            'send_id' => $send->id,
            'type' => SendFeedbackType::COMPLAINT->value,
            'url' => null,
            'user_agent' => null,
            'ip_address' => null,
            'happened_at' => now(),
        ]);
    }

    private function resolveSend(array $eventData): ?Send
    {
        $uuid = $eventData['user-variables']['send_uuid'] ?? null;

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return Send::with(['sendable.campaign.emailList', 'subscriber'])
            ->where('uuid', $uuid)
            ->first();
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('services.mailgun.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $timestamp = (string) $request->input('signature.timestamp', '');
        $token = (string) $request->input('signature.token', '');
        $signature = (string) $request->input('signature.signature', '');

        $computed = hash_hmac('sha256', $timestamp.$token, $secret);

        return hash_equals($computed, $signature);
    }
}
