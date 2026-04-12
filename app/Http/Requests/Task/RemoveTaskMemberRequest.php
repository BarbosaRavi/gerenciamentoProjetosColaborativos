<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class RemoveTaskMemberRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'user_id' => ['required', 'integer'],
        ];
    }
}
