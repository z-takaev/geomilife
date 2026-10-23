<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class QuestionSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $question,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новый вопрос с сайта',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.question-submitted',
        );
    }
}
