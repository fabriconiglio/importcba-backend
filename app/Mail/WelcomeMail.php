<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "¡Bienvenido a " . config('app.name') . "!",
            tags: ['welcome', 'registration'],
            metadata: [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.welcome',
            with: [
                'user' => $this->user,
                'loginUrl' => config('app.frontend_url') . '/login',
                'catalogUrl' => config('app.frontend_url') . '/catalog',
                'supportEmail' => config('mail.from.address'),
                'companyName' => config('app.name'),
                'companyLogo' => config('app.url') . '/images/logo.png',
                'socialLinks' => [
                    'instagram' => config('app.social.instagram', '#'),
                    'twitter' => config('app.social.twitter', '#'),
                ],
            ],
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
