<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serviceId = $this->route('service')?->id ?? $this->route('service');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('services', 'code')->ignore($serviceId),
            ],
            'name' => 'required|string|max:150',
            'direction_id' => 'required|exists:directions,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé par un autre service.',
            'name.required' => 'Le libellé est obligatoire.',
            'direction_id.required' => 'La direction parente est obligatoire.',
            'direction_id.exists' => 'La direction sélectionnée est invalide.',
        ];
    }
}
