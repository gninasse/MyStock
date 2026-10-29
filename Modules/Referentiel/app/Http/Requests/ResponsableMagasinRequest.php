<?php

namespace Modules\Referentiel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Referentiel\Models\ResponsableMagasin;

class ResponsableMagasinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_responsable' => ['required', Rule::in([ResponsableMagasin::PRINCIPAL, ResponsableMagasin::ADJOINT])],
            'date_debut' => ['required', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Veuillez choisir un utilisateur',
            'role_responsable.required' => 'Veuillez choisir un rôle',
            'date_debut.required' => 'La date de début est obligatoire',
        ];
    }
}
