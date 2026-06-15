<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SendFeedbackType;
use App\Models\CampaignDispatch;
use App\Models\Send;
use App\Models\SendFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CampaignTrackingController extends Controller
{
    /**
     * A 1×1 transparent GIF.
     */
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /**
     * Record an open and return the tracking pixel.
     */
    public function open(Request $request, Send $send): Response
    {
        $this->record($send, SendFeedbackType::OPEN, $request);

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Record a click and redirect to the original URL.
     */
    public function click(Request $request, Send $send): RedirectResponse
    {
        $url = (string) $request->query('u');

        abort_unless(str_starts_with($url, 'http://') || str_starts_with($url, 'https://'), 404);

        $this->record($send, SendFeedbackType::CLICK, $request, $url);

        return redirect()->away($url);
    }

    /**
     * Persist the event, set the first-event timestamp, and roll up the
     * dispatch's counters.
     */
    private function record(Send $send, SendFeedbackType $type, Request $request, ?string $url = null): void
    {
        $column = $type === SendFeedbackType::OPEN ? 'opened_at' : 'clicked_at';
        $isFirst = $send->{$column} === null;

        if ($isFirst) {
            $send->forceFill([$column => now()])->save();
        }

        $dispatch = $send->sendable;

        if ($dispatch instanceof CampaignDispatch) {
            if ($type === SendFeedbackType::OPEN) {
                $dispatch->increment('open_count');
                $isFirst && $dispatch->increment('unique_open_count');
            } else {
                $dispatch->increment('click_count');
                $isFirst && $dispatch->increment('unique_click_count');
            }
        }

        SendFeedback::create([
            'send_id' => $send->id,
            'type' => $type->value,
            'url' => $url,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'happened_at' => now(),
        ]);
    }
}
