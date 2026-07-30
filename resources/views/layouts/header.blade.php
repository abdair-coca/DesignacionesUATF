<header class="bg-white border-b border-gray-200 h-14 flex items-center justify-between px-6 shrink-0 shadow-sm z-10">
    <div class="flex items-center gap-4">
        <!-- Título Institucional / Contexto -->
        <div class="flex items-center gap-2">
            <span class="bg-[#00acac] text-white text-xs font-bold px-2 py-0.5 rounded shadow-sm">UATF</span>
            <h1 class="text-gray-800 text-sm font-semibold tracking-tight hidden sm:block">
                Sistema de Designación Docente
            </h1>
        </div>
    </div>

    <!-- Acciones Rápidas & Perfil de Usuario -->
    <div class="flex items-center gap-4">
        <!-- Notificaciones / Estado -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span class="absolute top-1 right-1 w-2 h-2 bg-[#00acac] rounded-full ring-2 ring-white"></span>
            </button>
        </div>

        <!-- Usuario Logueado (Director de Carrera / Admin) -->
        <div class="flex items-center gap-3 border-l border-gray-200 pl-4" x-data="{ open: false }">
            <div class="text-right hidden sm:block">
                <p class="text-xs font-bold text-gray-800 leading-tight">
                    {{ Auth::user()?->name ?? 'Usuario' }}
                </p>
                <p class="text-[10px] text-gray-500 font-medium">
                    {{ Auth::user()?->email ?? '' }}
                </p>
            </div>
            
            <div class="relative">
                <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                    <div class="h-8 w-8 rounded-full bg-[#2d353c] text-white font-bold text-xs flex items-center justify-center ring-2 ring-[#00acac]/30">
                        {{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 1)) }}
                    </div>
                </button>

                <!-- Menú desplegable Perfil -->
                <div x-show="open" @click.away="open = false" x-transition 
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 text-xs text-gray-700 z-50">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="font-semibold text-gray-900">{{ Auth::user()?->name ?? 'Usuario' }}</p>
                        <p class="text-[11px] text-gray-400">Rol: {{ Auth::user()?->esVicerrectorado() ? 'Vicerrectorado' : 'Director de Carrera' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
