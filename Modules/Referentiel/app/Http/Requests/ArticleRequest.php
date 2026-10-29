<?php

namespace Modules\Referentiel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Referentiel\Models\Article;

class ArticleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->code));

        // Code laissé vide à la création : généré automatiquement
        $this->merge(['code' => $code === '' && ! $this->route('id') ? Article::prochainCode() : $code]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('articles', 'code')->ignore($this->route('id'))],
            'designation' => ['required', 'string', 'max:200'],
            'unite_mesure' => ['required', Rule::in(array_keys(config('referentiel.unites_mesure')))],
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'stock_minimum' => ['nullable', 'integer', 'min:0'],
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
            'designation.required' => 'La désignation est obligatoire',
            'unite_mesure.required' => 'L\'unité de mesure est obligatoire',
            'unite_mesure.in' => 'Unité de mesure inconnue',
            'categorie_id.required' => 'La catégorie est obligatoire',
            'categorie_id.exists' => 'Catégorie inconnue',
            'stock_minimum.min' => 'Le stock minimum doit être positif ou nul',
        ];
    }
}
