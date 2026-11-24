<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('lyra-payment::views.return.title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 40rem; margin: 3rem auto; padding: 0 1rem; color: #1f2937; }
        .box { padding: 1rem 1.25rem; border-radius: .5rem; }
        .ok { background: #dcfce7; } .pending { background: #e0f2fe; } .ko { background: #fee2e2; }
        dl { display: grid; grid-template-columns: max-content 1fr; gap: .25rem 1rem; }
        dt { color: #6b7280; }
    </style>
</head>
<body>
@php($outcome = $payment->outcome())
<div class="box {{ match ($outcome) { 'accepted' => 'ok', 'pending' => 'pending', default => 'ko' } }}">
    <h1>
        @switch($outcome)
            @case('accepted') {{ __('lyra-payment::views.return.accepted') }} @break
            @case('pending') {{ __('lyra-payment::views.return.pending') }} @break
            @case('cancelled') {{ __('lyra-payment::views.return.cancelled') }} @break
            @case('invalid') {{ __('lyra-payment::views.return.invalid') }} @break
            @default {{ __('lyra-payment::views.return.failed') }}
        @endswitch
    </h1>

    @if ($payment->isAuthentic())
        <p>{{ $payment->message() }}</p>
        <dl>
            <dt>{{ __('lyra-payment::views.return.order') }}</dt><dd>{{ $payment->orderId() }}</dd>
            <dt>{{ __('lyra-payment::views.return.transaction') }}</dt><dd>{{ $payment->transId() }}</dd>
            <dt>{{ __('lyra-payment::views.return.status') }}</dt><dd>{{ $payment->transStatus() }}</dd>
        </dl>
    @endif
</div>
<p><a href="{{ url('/') }}">{{ __('lyra-payment::views.return.back') }}</a></p>
</body>
</html>
