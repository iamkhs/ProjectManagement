<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubTaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title' => 'sometimes|nullable|max:25',
            'description' => 'sometimes|nullable|max:255',
            'status' => ['sometimes', Rule::in(['pending', 'in-progress', 'completed'])],
            'due_date' => 'sometimes|nullable|date',
        ];
    }
}
