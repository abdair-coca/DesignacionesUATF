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

        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center justify-between px-4 py-2.5 transition-all duration-150 {{ request()->routeIs('dashboard*') ? 'bg-[#20252a] text-[#00acac] font-semibold border-l-4 border-[#00acac]' : 'hover:bg-[#23282c] hover:text-white text-[#a8b6c1]' }}">
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 {{ request()->routeIs('dashboard*') ? 'text-[#00acac]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </div>
            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>

        <!-- Designaciones (Grupo Desplegable con Alpine.js) -->
        <div x-data="{ open: {{ request()->routeIs('designaciones*') || request()->routeIs('revisiones*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-2.5 transition-all duration-150 {{ request()->routeIs('designaciones*') || request()->routeIs('revisiones*') ? 'bg-[#20252a] text-white font-semibold' : 'hover:bg-[#23282c] hover:text-white text-[#a8b6c1]' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-[#00acac]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Designaciones</span>
                    <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">NUEVO</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Submenú Árbol -->
            <div x-show="open" x-transition class="bg-[#1d2226] py-1.5 pl-6 pr-2 space-y-1">
                @php
                    $carreraId = \App\Models\Carrera::first()->id ?? 1;
                @endphp
                <a href="{{ route('designaciones.carrera', $carreraId) }}" 
                   class="flex items-center gap-2.5 px-3 py-2 rounded text-[11px] transition-colors {{ request()->routeIs('designaciones.carrera*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Por Docente (Roster)</span>
                </a>
                <a href="{{ route('designaciones.lista') }}" 
                   class="flex items-center gap-2.5 px-3 py-2 rounded text-[11px] transition-colors {{ request()->routeIs('designaciones.lista*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Lista de Designaciones</span>
                </a>
                <a href="{{ route('revisiones.pendientes') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded text-[11px] transition-colors {{ request()->routeIs('revisiones*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                        <span>Revisiones</span>
                    </div>
                    <span class="bg-[#1a2229] text-gray-300 text-[10px] font-semibold px-2 py-0.5 rounded-full">Pendientes</span>
                </a>
            </div>
        </div>

        <!-- Catálogos (Grupo Desplegable) -->
        <div x-data="{ open: {{ request()->routeIs('docentes*') || request()->routeIs('materias*') || request()->routeIs('grupos*') || request()->routeIs('carreras*') || request()->routeIs('gestiones*') || request()->routeIs('periodos*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-2.5 transition-all duration-150 {{ request()->routeIs('docentes*') || request()->routeIs('materias*') || request()->routeIs('grupos*') || request()->routeIs('carreras*') || request()->routeIs('gestiones*') || request()->routeIs('periodos*') ? 'bg-[#20252a] text-white font-semibold' : 'hover:bg-[#23282c] hover:text-white text-[#a8b6c1]' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Catálogos</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Submenú Árbol Catálogos -->
            <div x-show="open" x-transition class="bg-[#1d2226] py-1.5 pl-6 pr-2 space-y-1">
                <a href="{{ route('docentes.index') }}" 
                   class="flex items-center gap-2.5 px-3 py-1.5 rounded text-[11px] transition-colors {{ request()->routeIs('docentes*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Docentes</span>
                </a>
                <a href="{{ route('materias.index') }}" 
                   class="flex items-center gap-2.5 px-3 py-1.5 rounded text-[11px] transition-colors {{ request()->routeIs('materias*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Materias</span>
                </a>
                <a href="{{ route('grupos.index') }}" 
                   class="flex items-center gap-2.5 px-3 py-1.5 rounded text-[11px] transition-colors {{ request()->routeIs('grupos*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Grupos</span>
                </a>
                <a href="{{ route('carreras.index') }}" 
                   class="flex items-center gap-2.5 px-3 py-1.5 rounded text-[11px] transition-colors {{ request()->routeIs('carreras*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Carreras</span>
                </a>
                <a href="{{ route('gestiones.index') }}" 
                   class="flex items-center gap-2.5 px-3 py-1.5 rounded text-[11px] transition-colors {{ request()->routeIs('gestiones*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Gestiones</span>
                </a>
                <a href="{{ route('periodos.index') }}" 
                   class="flex items-center gap-2.5 px-3 py-1.5 rounded text-[11px] transition-colors {{ request()->routeIs('periodos*') ? 'text-[#00acac] font-bold bg-[#15191c]' : 'text-[#a8b6c1] hover:text-white hover:bg-[#23282c]' }}">
                    <span class="w-1.5 h-1.5 rounded-full border border-current"></span>
                    <span>Periodos</span>
                </a>
            </div>
        </div>
    </nav>
</aside>
