<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:pending,in_progress,on_hold,completed'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
