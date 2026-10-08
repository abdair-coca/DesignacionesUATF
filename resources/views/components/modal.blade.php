@props([
    'open',
    'titleId',
    'close',
    'kicker' => null,
    'form' => false,
    'submit' => null,
    'closeDisabled' => 'false',
    'dialogClass' => '',
    'closeLabel' => 'Cerrar modal',
])

<div
    x-show="{{ $open }}"
    x-cloak
    x-transition.opacity
    class="app-modal no-print"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $titleId }}"
    @keydown.escape.window="{{ $close }}"
>
    <div class="app-modal-dialog {{ $dialogClass }}" @click.outside="{{ $close }}">
        @if($form)
            <form class="app-modal-content" @submit.prevent="{{ $submit }}">
        @else
            <div class="app-modal-content">
        @endif
            <div class="app-modal-header">
                <div>
                    @if($kicker)
                        <span class="app-modal-kicker">{{ $kicker }}</span>
                    @endif
                    <h2 id="{{ $titleId }}" class="app-modal-title">{{ $title }}</h2>
                </div>
                <button
                    type="button"
                    @click="{{ $close }}"
                    :disabled="{{ $closeDisabled }}"
                    class="app-modal-close"
                    aria-label="{{ $closeLabel }}"
                >&times;</button>
            </div>
            <div class="app-modal-body">{{ $body ?? $slot }}</div>
            @isset($footer)
                <div class="app-modal-footer">{{ $footer }}</div>
            @endisset
        @if($form)
            </form>
        @else
            </div>
        @endif
    </div>
</div>
