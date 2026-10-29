<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Service;
use Modules\Referentiel\Models\Unite;
use Modules\Stock\Services\MouvementService;

/**
 * Saisie d'une entrée ou d'une sortie. Les contrôles bloquants propres
 * à la validation (au moins une ligne, stock suffisant…) sont dans MouvementService.
 */
class DocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['brouillon', 'valider'])],
            'date_mouvement' => ['required', 'date_format:Y-m-d'],
            'magasin_id' => ['required', 'integer', 'exists:magasins,id'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'unite_id' => ['nullable', 'integer', 'exists:unites,id'],
            'commentaire' => ['nullable', 'string', 'max:500'],
            'lignes' => ['nullable', 'array'],
            'lignes.*.article_id' => ['required', 'integer', 'exists:articles,id'],
            'lignes.*.quantite' => ['required', 'integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($message = app(MouvementService::class)->erreurDate($this->date_mouvement)) {
                    $validator->errors()->add('date_mouvement', $message);
                }

                foreach ($this->input('lignes', []) as $index => $ligne) {
                    if ((int) $ligne['quantite'] <= 0) {
                        $designation = Article::find($ligne['article_id'])?->designation;
                        $validator->errors()->add("lignes.{$index}.quantite", "La quantité doit être supérieure à zéro pour l'article {$designation}.");
                    }
                }

                if ($this->service_id && $this->direction_id
                    && Service::whereKey($this->service_id)->value('direction_id') != $this->direction_id) {
                    $validator->errors()->add('service_id', 'Ce Service n\'appartient pas à la Direction choisie.');
                }

                if ($this->unite_id && Unite::whereKey($this->unite_id)->value('service_id') != $this->service_id) {
                    $validator->errors()->add('unite_id', 'Cette Unité n\'appartient pas au Service choisi.');
                }
            },
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'magasin_id.required' => 'Veuillez sélectionner un magasin.',
            'date_mouvement.required' => 'La date du mouvement est obligatoire.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 500 caractères.',
        ];
    }
}
