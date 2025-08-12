<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

//Extending Notification is for creating a general-purpose notification,
// while extending ResetPasswordNotification is specifically for creating a password reset email.
class CustomResetPassword extends ResetPasswordNotification
{
    use Queueable;
    /**
     * @var string
     */
    public $token;
    /**
     * Create a new notification instance.
     * @param string $token
     * @return void
     */
    public function __construct(string $token)
    {
        $this->token=$token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        //that URL should point to your frontend application's password reset page, not a Laravel route. 
        // This is a temporary URL for testing the backend.
        // It uses your backend's base URL from the .env file.
         $url = config('app.url') . '/api/password/reset?token=' . $this->token . '&email=' . $notifiable->getEmailForPasswordReset();

        return (new MailMessage)
            ->subject('Password Reset Request')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in ' . config('auth.passwords.users.expire') . ' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
