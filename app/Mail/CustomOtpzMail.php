<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CustomOtpzMail extends Mailable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    /**
     * Create a new message instance.
     */
    public function __construct(protected Otp $otp, protected string $code)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $user = $this->otp->user;
        // Définir la locale pour les traductions
        if ($user && $user->exists && method_exists($user, 'getLocale')) {
            app()->setLocale($user->getLocale());
        }

        return new Envelope(
            subject: __('email.otpz_greeting').' '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $user = $this->otp->user;
        $email = $this->otp->user->email;
        // Définir la locale pour les traductions
        if ($user && $user->exists && method_exists($user, 'getLocale')) {
            app()->setLocale($user->getLocale());
        }

        // Utiliser le template selon la configuration
        $template = config('otpz.template', 'otpz::mail.otpz');

        return new Content(
            view: $template,
            with: [
                'code' => $this->code,
                'email' => $email,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
