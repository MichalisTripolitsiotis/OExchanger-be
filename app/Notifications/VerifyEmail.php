<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as NotificationsVerifyEmail;

class VerifyEmail extends NotificationsVerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     *
     * @param mixed $notifiable
     *
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        $payload = $this->getToken($notifiable);

        return '?token=' . $payload;
    }

    /**
     * Get a token for the given notifiable.
     *
     * @param mixed $notifiable
     *
     * @return string
     */
    protected function getToken($notifiable)
    {
        return base64_encode(json_encode([
            'hash' => encrypt($notifiable->getEmailForVerification())
        ]));
    }
}