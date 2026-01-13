<?php

namespace App\Notifications;

use App\Http\Resources\AppSettingsResource;
use App\Models\User;
use App\Settings\AppSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class sendEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $type;

    public $user;

    public $verificationUrl;

    public $lang;

    public $logo;

    public $code;

    /**
     * Create a new notification instance.
     */
    public function __construct($type, User $user, $verificationUrl, $lang, $code = null)
    {
        $this->user = $user;
        $this->lang = $lang;
        $this->code = $code;
        $this->type = $type;
        $this->verificationUrl = $verificationUrl;
        $settings = new AppSettings;
        $this->logo = new AppSettingsResource($settings);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Définir la locale pour les traductions
        app()->setLocale($this->lang);

        // Définition des variables selon le type d'email avec traductions
        switch ($this->type) {
            case 'verify_email':
                $title = __('email.verify_email_title');
                $text = __('email.verify_email_text');
                $textBtn = __('email.verify_email_button');
                break;
            case 'reset_password':
                $title = __('email.reset_password_title');
                $text = __('email.reset_password_text');
                $textBtn = __('email.reset_password_button');
                break;
            case 'notification_rappel':
                $title = __('email.security_reminder_title');
                $text = __('email.security_reminder_text');
                $textBtn = __('email.security_reminder_button');
                break;
            case 'account_blocked':
                $title = __('email.account_blocked_title');
                $text = __('email.account_blocked_text');
                $textBtn = __('email.account_blocked_button');
                break;
            case 'account_unblocked':
                $title = __('email.account_unblocked_title');
                $text = __('email.account_unblocked_text');
                $textBtn = __('email.account_unblocked_button');
                break;
            case 'otpz':
                $title = __('email.otpz_greeting');
                $text = __('email.otpz_copy', ['email' => $this->user->email]);
                $textBtn = __('email.default_button');
                break;
            default:
                // Valeurs par défaut avec traduction
                $title = __('email.default_title');
                $text = __('email.default_text');
                $textBtn = __('email.default_button');
                break;
        }

        $from_address = Config('mail.from.address');
        if ($this->code) {
            $parameter = [
                'url' => $this->verificationUrl,
                'title' => $title,
                'text' => $text,
                'code' => $this->code,
                'textBtn' => $textBtn,
                'logo' => $this->logo->site_logo ?? config('app.url_frontend').'assets/img/logo/logo.svg',
            ];
        } else {
            $parameter = [
                'url' => $this->verificationUrl,
                'title' => $title,
                'text' => $text,
                'code' => $this->code,
                'textBtn' => $textBtn,
                'logo' => $this->logo->site_logo ?? config('app.url_frontend').'assets/img/logo/logo.svg',
            ];
        }

        return (new MailMessage)
            ->subject($title)
            ->from($from_address)
            ->view('emails.email', $parameter);
    }
}
