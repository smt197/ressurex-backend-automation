<x-mail::message>
{{-- Greeting --}}
# {{ __('email.otpz_notification_hello') }}

{{-- Intro Lines --}}
{{ __('email.otpz_notification_intro') }}

{{-- Action Button --}}
<x-mail::button :url="$url">
{{ __('email.otpz_notification_button') }} {{ config('app.name') }}
</x-mail::button>

{{-- Outro Lines --}}
{{ __('email.otpz_notification_outro') }}

{{-- Salutation --}}
{{ __('email.otpz_notification_thanks') }} {{ config('app.name') }}!

{{-- Subcopy --}}
<x-slot:subcopy>
{{ __('email.otpz_notification_trouble', ['actionText' => __('email.otpz_notification_button') . ' ' . config('app.name')]) }}
<span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
