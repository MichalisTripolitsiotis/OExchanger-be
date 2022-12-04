<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;

final class ForgotPassword
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $email = $args['email'];
        $callback_url = $args['callbackUrl'];

        $user = User::where('email', $email)->first();

        $status = Password::sendResetLink(['email' => $email], function ($user, $token) use ($callback_url) {
            $resetNotification = new ResetPassword($token);
            $resetNotification->createUrlUsing(function ($user, $token) use ($callback_url) {
                $url = trim($callback_url, '/');
                $email = $user->getEmailForPasswordReset();
                return url($url . '?token=' . $token . '&email=' . $email);
            });
            $user->notify($resetNotification);
        });

        if ($status === Password::INVALID_USER) {
            abort(403, "Invalid email address provided");
        }

        if ($status === Password::RESET_THROTTLED) {
            abort(401, "Password reset attempts have been throttled, try again later");
        }

        if ($status === Password::RESET_LINK_SENT) {
            return true;
        }
    }
}
