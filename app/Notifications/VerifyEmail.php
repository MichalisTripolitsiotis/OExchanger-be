<?php

namespace App\Notifications;

use Carbon\Carbon;
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

        // The reason we get the url, is to be used from the FE
        // in order to send the user to a specific url.
        return $notifiable->url . '?token=' . $payload;
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
            'id'         => $notifiable->getKey(),
            'hash'       => encrypt($notifiable->getEmailForVerification()),
            'expiration' => encrypt(Carbon::now()->addMinutes(10)->toIso8601String()),
        ]));
    }
}
