<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DirectionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $directionId = $this->route('direction')?->id ?? $this->route('direction');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('directions', 'code')->ignore($directionId),
            ],
            'name' => 'required|string|max:150',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé par une autre direction.',
            'name.required' => 'Le libellé est obligatoire.',
        ];
    }
}
