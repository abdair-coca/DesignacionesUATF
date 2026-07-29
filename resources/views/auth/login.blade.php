@extends('layouts.app')

@section('title', 'Iniciar Sesión — UATF Designación Docente')

@section('content')
<div class="w-full max-w-md bg-white rounded-lg shadow-2xl overflow-hidden border border-gray-700/30">
    <!-- Header del Login estilo Color Admin v2 -->
    <div class="bg-[#2d353c] p-6 text-center border-b border-black/20 relative" 
         style="background-image: linear-gradient(rgba(45, 53, 60, 0.85), rgba(45, 53, 60, 0.95)), url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=600&q=80');">
        <div class="inline-flex h-12 w-12 rounded-full bg-[#00acac] text-white font-black text-2xl items-center justify-center shadow-lg border-2 border-white/20 mb-3">
            U
        </div>
        <h2 class="text-white text-lg font-bold tracking-tight">Universidad Autónoma Tomás Frías</h2>
        <p class="text-xs text-gray-300 font-medium mt-1">Sistema de Designación Docente</p>
    </div>

    <!-- Formulario de Login -->
    <div class="p-6 space-y-5">
        @if ($errors->any())
            <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-xs font-semibold flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Correo Electrónico
                </label>
                <div class="relative">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-3.5 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00acac] focus:border-[#00acac] transition-all"
                           placeholder="correo@uatf.edu.bo">
                </div>
            </div>

            <!-- Contraseña -->
            <div>
                <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Contraseña
                </label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3.5 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00acac] focus:border-[#00acac] transition-all"
                       placeholder="••••••••">
            </div>

            <!-- Recordar sesión -->
            <div class="flex items-center justify-between text-xs text-gray-600">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                    <span>Recordar sesión</span>
                </label>
            </div>

            <!-- Botón de Ingreso -->
            <button type="submit" 
                    class="w-full bg-[#00acac] hover:bg-[#008a8a] text-white font-bold py-2.5 px-4 rounded-lg text-sm transition-all duration-150 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[#00acac] focus:ring-offset-2">
                Ingresar al Sistema
            </button>
        </form>
    </div>
</div>
@endsection
