<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Designación de Docentes') — UATF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <nav class="bg-dark text-white p-3" style="width: 260px; flex-shrink: 0;">
            <h5 class="mb-4">UATF · Designaciones</h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white {{ request()->routeIs('designaciones.*') ? 'fw-bold' : '' }}"
                       href="{{ route('designaciones.index') }}">
                        Designación de Docentes
                    </a>
                </li>
            </ul>
        </nav>

        <main class="flex-grow-1 p-4">
            <h2 class="mb-4">@yield('title', 'Designación de Docentes')</h2>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
