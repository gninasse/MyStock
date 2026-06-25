<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')?->id ?? $this->route('unit');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'code')->ignore($unitId),
            ],
            'name' => 'required|string|max:150',
            'service_id' => 'required|exists:services,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé par une autre unité.',
            'name.required' => 'Le libellé est obligatoire.',
            'service_id.required' => 'Le service parent est obligatoire.',
            'service_id.exists' => 'Le service sélectionné est invalide.',
        ];
    }
}
