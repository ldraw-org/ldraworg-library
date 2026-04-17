<?php

namespace App\Mail;

use App\Collections\PartCollection;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DailyDigest extends Mailable
{
    use Queueable;
    use SerializesModels;

    public PartCollection $parts;
    public Carbon $date;
    public Carbon $next;

    public function __construct(
        protected User $user
    )
    {
        $this->date = now()->subDay();
        $this->next = $this->date->copy()->endOfDay();
    }

    public function envelope(): Envelope
   {
        return new Envelope(
            subject: 'Parts Tracker Daily Summary for ' . $this->date->copy()->format('Y-m-d'),
        );
    }

    public function content(): Content
    {
        $this->parts = Part::unofficial()
            ->whereHas('notification_users', fn ($q) => $q->where('id', $this->user->id))
            ->whereHas('events', fn ($q) => $q->whereNull('part_release_id')
                ->whereBetween('created_at', [$this->date->copy()->startOfDay(), $this->date->copy()->endOfDay()]))
            ->get();
        return new Content(markdown: 'emails.dailydigest-markdown');
    }

    public function attachments(): array
    {
        return [];
    }
}
