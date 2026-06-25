<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeId = $this->route('store')?->id ?? $this->route('store');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('stores', 'code')->ignore($storeId),
            ],
            'name' => 'required|string|max:150',
            'location' => 'nullable|string|max:200',
            'manager_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ];
    }
}
