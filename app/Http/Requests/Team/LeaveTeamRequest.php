<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class LeaveTeamRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [];
    }
}
