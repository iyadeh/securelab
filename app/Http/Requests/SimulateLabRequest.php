<?php

namespace App\Http\Requests;

use App\Models\Lab;
use App\Services\Labs\Contracts\LabRuntime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;

#[FailOnUnknownFields]
class SimulateLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(LabRuntime $runtime): array
    {
        $lab = $this->route('lab');

        if (! $lab instanceof Lab) {
            return [];
        }

        return $runtime->validationRules($lab);
    }
}
