<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('lyra-payment::views.redirect.title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 48rem; margin: 3rem auto; padding: 0 1rem; color: #1f2937; }
        table { border-collapse: collapse; width: 100%; font-size: .875rem; margin: 1rem 0; }
        td { border-bottom: 1px solid #e5e7eb; padding: .35rem .5rem; word-break: break-all; }
        td:first-child { font-family: ui-monospace, monospace; white-space: nowrap; }
        .warn { background: #fef3c7; padding: .75rem 1rem; border-radius: .375rem; }
        button { font: inherit; padding: .6rem 1.4rem; cursor: pointer; }
    </style>
</head>
<body>
<form id="lyra-payment-form" action="{{ $action }}" method="POST">
    @foreach ($fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    @if ($debug)
        <p class="warn">{!! __('lyra-payment::views.redirect.debug', ['action' => e($action)]) !!}</p>
        <table>
            @foreach ($fields as $name => $value)
                <tr><td>{{ $name }}</td><td>{{ $value }}</td></tr>
            @endforeach
        </table>
        <button type="submit">{{ __('lyra-payment::views.redirect.pay') }}</button>
    @else
        <p>{{ __('lyra-payment::views.redirect.title') }}</p>
        <noscript><button type="submit">{{ __('lyra-payment::views.redirect.continue') }}</button></noscript>
    @endif
</form>

@unless ($debug)
    @php($nonce = \Illuminate\Support\Facades\Vite::cspNonce())
    <script @if ($nonce) nonce="{{ $nonce }}" @endif>
        document.getElementById('lyra-payment-form').submit();
    </script>
@endunless
</body>
</html>
