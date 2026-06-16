<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $editUrl;

    public function __construct(public Campaign $campaign)
    {
        $this->editUrl = route('campaigns.edit', $campaign);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: "'.$this->campaign->name.'" sends tomorrow',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.campaign-reminder',
        );
    }
}
