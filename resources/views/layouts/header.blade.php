<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<header class="odiseo-top-header">
    <div class="top-brand">
        <span class="top-brand-mark">UATF</span>
        <h1 class="top-brand-title">Sistema ODISEO</h1>
    </div>

    <div class="top-actions">
        @php($notificacionesNoLeidas = Auth::user()?->unreadNotifications()->count() ?? 0)
        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" @keydown.escape.window="open = false" class="top-action" title="Notificaciones" aria-label="Notificaciones" :aria-expanded="open">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if($notificacionesNoLeidas > 0)
                    <span class="notification-count">{{ $notificacionesNoLeidas }}</span>
                @endif
            </button>

            <div x-cloak x-show="open" @click.outside="open = false" x-transition class="top-menu odiseo-notification-menu">
                <div class="top-menu-title">
                    <strong>Notificaciones</strong>
                    @if($notificacionesNoLeidas > 0)
                        <form method="POST" action="{{ route('notificaciones.leer_todas') }}">
                            @csrf
                            <button type="submit" class="top-menu-link top-menu-mark-read">Marcar leídas</button>
                        </form>
                    @endif
                </div>

                @php($notificacionesRecientes = Auth::user()?->unreadNotifications()->latest()->limit(5)->get() ?? collect())
                @forelse($notificacionesRecientes as $notificacion)
                    <form method="POST" action="{{ route('notificaciones.leer', $notificacion) }}">
                        @csrf
                        <button type="submit" @click="open = false" class="odiseo-notification-item">
                            <p class="notification-title">{{ $notificacion->data['titulo'] ?? 'Actualización' }}</p>
                            <p class="notification-detail">{{ $notificacion->data['detalle'] ?? '' }}</p>
                            <p class="notification-date">{{ $notificacion->created_at->format('d/m/Y H:i') }}</p>
                        </button>
                    </form>
                @empty
                    <p class="odiseo-notification-empty">No hay notificaciones nuevas.</p>
                @endforelse

                <div class="odiseo-notification-footer">
                    <a href="{{ route('notificaciones.index') }}">Ver todo</a>
                </div>
            </div>
        </div>

        <div class="top-user" x-data="{ open: false }">
            <div class="top-user-copy">
                <p class="top-user-name">{{ Auth::user()?->name ?? 'Usuario' }}</p>
                <p class="top-user-role">{{ Auth::user()?->esVicerrectorado() ? 'Vicerrectorado' : (Auth::user()?->esDecanatura() ? 'Decanatura' : 'Director de Carrera') }}</p>
            </div>

            <div class="relative">
                <button type="button" @click="open = !open" @keydown.escape.window="open = false" class="top-action" aria-label="Abrir menú de usuario" :aria-expanded="open">
                    <span class="top-avatar">{{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 1)) }}</span>
                </button>

                <div x-cloak x-show="open" @click.outside="open = false" x-transition class="top-menu">
                    <div class="top-menu-title">
                        <strong>{{ Auth::user()?->name ?? 'Usuario' }}</strong>
                        <span>{{ Auth::user()?->email ?? '' }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="top-menu-link danger">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
