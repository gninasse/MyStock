<?php

namespace Modules\Referentiel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MagasinRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->code))]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('magasins', 'code')->ignore($this->route('id'))],
            'libelle' => ['required', 'string', 'max:100'],
            'localisation' => ['nullable', 'string', 'max:200'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire',
            'code.regex' => 'Le code ne peut contenir que des lettres, chiffres, tirets et soulignés',
            'code.unique' => 'Ce code existe déjà. Veuillez en choisir un autre.',
            'libelle.required' => 'Le libellé est obligatoire',
        ];
    }
}
