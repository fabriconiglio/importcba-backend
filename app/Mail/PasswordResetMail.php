<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetUrl;
    public string $token;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->resetUrl = config('app.frontend_url') . "/reset-password?token={$token}&email=" . urlencode($user->email);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperación de Contraseña',
            tags: ['password', 'reset'],
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
            view: 'emails.auth.password-reset',
            with: [
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
                'token' => $this->token,
                'expiresAt' => now()->addHours(1)->format('d/m/Y H:i'),
                'supportEmail' => config('mail.from.address'),
                'companyName' => config('app.name'),
                'companyLogo' => config('app.url') . '/images/logo.png',
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
