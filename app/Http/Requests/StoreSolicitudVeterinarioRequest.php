<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudVeterinarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'finca_id'           => 'required|integer|exists:fincas,id',
            'correo_veterinario' => 'required|email|max:255',
        ];
    }
}
