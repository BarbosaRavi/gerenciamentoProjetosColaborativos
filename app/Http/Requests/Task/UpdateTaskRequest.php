<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'title' => ['sometimes', 'string', 'min:3', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:pending,in_progress,on_hold,completed'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
