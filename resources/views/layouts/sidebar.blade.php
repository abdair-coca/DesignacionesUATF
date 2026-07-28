<aside className="sidebar" class="w-64 bg-[#2d353c] text-[#a8b6c1] flex flex-col min-h-screen shrink-0 font-sans shadow-xl select-none">
    <!-- Header de Perfil / Universidad estilo Color Admin v2 -->
    <div class="relative p-4 bg-cover bg-center border-b border-black/20" style="background-image: linear-gradient(rgba(45, 53, 60, 0.75), rgba(45, 53, 60, 0.95)), url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=400&q=80');">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-[#00acac] text-white font-black text-xl flex items-center justify-center shadow-lg border-2 border-white/20">
                    U
                </div>
                <div class="overflow-hidden">
                    <h2 class="text-white font-bold text-sm leading-tight truncate" title="Universidad Autónoma Tomás Frías">
                        Universidad Autónoma Tomás Frías
                    </h2>
                    <p class="text-[11px] text-gray-300 mt-0.5 truncate font-medium">
                        Sistema de Designaciones
                    </p>
                </div>
            </div>
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </div>

    <!-- Navegación Principal -->
    <nav class="flex-1 px-0 py-3 overflow-y-auto space-y-1 text-xs font-medium">
        <div class="px-4 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-500">
            Navegación
        </div>

        @if(Auth::user()?->is_admin)
            <!-- Bandeja de Revisiones (Solo Vicerrectorado) -->
            <a href="{{ route('revisiones.pendientes') }}" 
               class="flex items-center justify-between px-4 py-2.5 transition-all duration-150 {{ request()->routeIs('revisiones*') ? 'bg-[#20252a] text-[#00acac] font-semibold border-l-4 border-[#00acac]' : 'hover:bg-[#23282c] hover:text-white text-[#a8b6c1]' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 {{ request()->routeIs('revisiones*') ? 'text-[#00acac]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Bandeja de Revisiones</span>
                </div>
                <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm uppercase tracking-wider">
                    INBOX
                </span>
            </a>
        @else
            <!-- Designaciones por Carrera (Solo Director) -->
            <a href="{{ route('designaciones.lista') }}" 
               class="flex items-center justify-between px-4 py-2.5 transition-all duration-150 {{ request()->routeIs('designaciones*') ? 'bg-[#20252a] text-[#00acac] font-semibold border-l-4 border-[#00acac]' : 'hover:bg-[#23282c] hover:text-white text-[#a8b6c1]' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 {{ request()->routeIs('designaciones*') ? 'text-[#00acac]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Designaciones por Carrera</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        @endif
    </nav>
</aside>
