<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $articleId = $this->route('article')?->id ?? $this->route('article');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('articles', 'code')->ignore($articleId),
            ],
            'designation' => 'required|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:30',
            'description' => 'nullable|string',
            'min_stock' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
