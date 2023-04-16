@component('mail::message')
# Email Verification

Your new four-digit code is {{$pin}}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
