<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstitutionalDesignacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || (! $user->esDirectorCarrera() && ! $user->esVicerrectorado())) {
            return false;
        }

        $programa = strtoupper(trim((string) $this->input('programa')));

        if ($programa === '' || $user->esVicerrectorado()) {
            return true;
        }

        return $programa !== 'UATF'
            && $programa === strtoupper((string) $user->carrera?->sigla);
    }

    public function rules(): array
    {
        return $this->parameterRules(true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function parameterRules(bool $required): array
    {
        $presence = $required ? ['required'] : ['nullable'];
        $crossField = $required ? [] : ['required_with:programa,gestion,periodo'];

        return [
            'programa' => [...$presence, ...$crossField, 'string', 'regex:/^[A-Za-z0-9_-]{2,20}$/'],
            'gestion' => [...$presence, ...$crossField, 'regex:/^(?:0|\d{4})$/'],
            'periodo' => [...$presence, ...$crossField, 'regex:/^(?:0|\d{1,2})$/'],
        ];
    }

    /**
     * @return array{programa: string, gestion: string, periodo: string}|null
     */
    public function parametros(): ?array
    {
        if (! $this->filled('programa') && ! $this->filled('gestion') && ! $this->filled('periodo')) {
            return null;
        }

        return [
            'programa' => strtoupper(trim((string) $this->input('programa'))),
            'gestion' => trim((string) $this->input('gestion')),
            'periodo' => trim((string) $this->input('periodo')),
        ];
    }
}
