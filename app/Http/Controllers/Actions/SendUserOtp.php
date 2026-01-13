<?php

namespace App\Http\Controllers\Actions;

use App\Notifications\sendEmailNotification;
use BenBjurstrom\Otpz\Exceptions\OtpThrottleException;
use BenBjurstrom\Otpz\Models\Otp;

/**
 * @method static Otp run(string $email)
 *
 * @throws OtpThrottleException
 */
class SendUserOtp
{
    public function handle(string $email, bool $remember = false): Otp
    {
        $userResolver = config('otpz.user_resolver', GetUserFromEmail::class);

        $user = (new $userResolver)->handle($email);
        [$otp, $code] = (new CreateOtp)->handle($user, $remember);
        $type = 'otpz';
        $user->notify(new sendEmailNotification($type, $user, config('app.url_frontend'), $user->getLocale(), $code));

        return $otp;
    }
}
