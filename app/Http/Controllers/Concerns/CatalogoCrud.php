<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait CatalogoCrud
{
    abstract protected function modelo(): string;

    abstract protected function reglas(Request $request, ?int $id = null): array;

    abstract protected function nombreEntidad(): string;

    abstract protected function rutaIndex(): string;

    protected function destroyRelacion(): ?string
    {
        return null;
    }

    protected function destroyEtiqueta(): string
    {
        return 'asociados';
    }

    public function store(Request $request): RedirectResponse
    {
        $this->modelo()::create($this->reglas($request));

        return redirect()->route($this->rutaIndex())
            ->with('status', $this->nombreEntidad().' creada correctamente.');
    }

    protected function actualizarModelo(Request $request, mixed $modelo): RedirectResponse
    {
        $modelo->update($this->reglas($request, $modelo->id));

        return redirect()->route($this->rutaIndex())
            ->with('status', $this->nombreEntidad().' actualizada correctamente.');
    }

    protected function eliminarModelo(mixed $modelo): RedirectResponse
    {
        $relacion = $this->destroyRelacion();

        if ($relacion && $modelo->$relacion()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar: '.$this->nombreEntidad().' tiene '.$this->destroyEtiqueta().' asociados.');
        }

        $modelo->delete();

        return redirect()->back()
            ->with('status', $this->nombreEntidad().' eliminada.');
    }
}
