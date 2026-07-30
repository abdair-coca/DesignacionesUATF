<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    public function index(): View
    {
        return view('notificaciones.index', [
            'notificaciones' => auth()->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function marcarLeida(DatabaseNotification $notificacion): RedirectResponse
    {
        abort_unless((int) $notificacion->notifiable_id === (int) auth()->id()
            && $notificacion->notifiable_type === auth()->user()::class, 403);

        $notificacion->markAsRead();

        $url = $notificacion->data['url'] ?? route('notificaciones.index');

        return redirect()->to(str_replace(
            ['/versiones/', '/propuestas/', '/propuesta-versiones/'],
            ['/revisiones/', '/designaciones/', '/designacion-versiones/'],
            $url,
        ));
    }
}
