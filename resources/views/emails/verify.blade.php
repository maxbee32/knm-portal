@component('mail::message')
# Email Verification

Thank you for signing up.
Your four-digit code is {{$pin}}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
