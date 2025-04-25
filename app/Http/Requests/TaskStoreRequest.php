<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title'=> 'required|string|max:25',
            'description'=> 'sometimes|nullable|string|max:255',
            'project_id'=> 'required|integer|exists:projects,id',
            'due_date'=> 'sometimes|date',
            'assigned_to'=> 'sometimes|nullable|integer|exists:users,id',
        ];
    }
}
