<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Status;
use App\Models\CampaignDispatch;
use App\Models\Send;
use App\Models\Subscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendCampaignDispatch implements ShouldQueue
{
    use Queueable;

    public function __construct(public CampaignDispatch $dispatch) {}

    public function handle(): void
    {
        $campaign = $this->dispatch->campaign;

        if ($campaign === null || $campaign->emailList === null) {
            return;
        }

        $baseHtml = $campaign->structured_html ?: ($campaign->html ?? '');
        $sent = 0;

        $campaign->emailList
            ->subscribers()
            ->wherePivot('status', Status::SUBSCRIBED->value)
            ->lazyById()
            ->each(function (Subscriber $subscriber) use ($campaign, $baseHtml, &$sent): void {
                $send = new Send;
                $send->uuid = (string) Str::ulid();
                $send->subscriber_id = $subscriber->id;
                $send->sent_at = now();
                $send->sendable()->associate($this->dispatch);
                $send->save();

                $html = $this->prepareHtml(
                    $baseHtml,
                    $send,
                    (bool) $campaign->track_opens,
                    (bool) $campaign->track_clicks,
                );

                Mail::html($html, function (Message $message) use ($campaign, $subscriber): void {
                    $message->to($subscriber->email)
                        ->subject($campaign->subject ?: $campaign->name);

                    if ($campaign->from_email) {
                        $message->from($campaign->from_email, $campaign->from_name ?: null);
                    }

                    if ($campaign->reply_to_email) {
                        $message->replyTo($campaign->reply_to_email);
                    }
                });

                $sent++;
            });

        $this->dispatch->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_to_count' => $sent,
        ]);
    }

    /**
     * Inject click tracking and an open pixel into a recipient's HTML.
     */
    private function prepareHtml(string $html, Send $send, bool $trackOpens, bool $trackClicks): string
    {
        if ($trackClicks) {
            $html = (string) preg_replace_callback(
                '/href="(https?:\/\/[^"]+)"/i',
                function (array $match) use ($send): string {
                    $url = route('campaigns.track.click', ['send' => $send->uuid, 'u' => $match[1]]);

                    return 'href="'.htmlspecialchars($url, ENT_QUOTES).'"';
                },
                $html,
            );
        }

        if ($trackOpens) {
            $pixel = '<img src="'.route('campaigns.track.open', ['send' => $send->uuid]).'" width="1" height="1" alt="" style="display:none" />';
            $html = str_contains($html, '</body>')
                ? str_replace('</body>', $pixel.'</body>', $html)
                : $html.$pixel;
        }

        return $html;
    }
}
