<?php

namespace Modules\Referentiel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategorieRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->code))]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('categories', 'code')->ignore($this->route('id'))],
            'libelle' => ['required', 'string', 'max:100'],
            'unite_par_defaut' => ['nullable', Rule::in(array_keys(config('referentiel.unites_mesure')))],
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
