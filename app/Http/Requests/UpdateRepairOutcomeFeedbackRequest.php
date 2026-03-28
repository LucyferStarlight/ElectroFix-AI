<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRepairOutcomeFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['worker', 'admin', 'developer'], true);
    }

    public function rules(): array
    {
        return [
            'diagnostic_accuracy' => ['required', Rule::in(['correct', 'partial', 'incorrect'])],
            'technician_notes' => ['nullable', 'string', 'max:1000'],
            'actual_causes' => ['nullable', 'array'],
            'actual_causes.*' => ['string', 'max:255'],
            'real_diagnosis' => ['nullable', 'array'],
            'real_diagnosis.summary' => ['nullable', 'string', 'max:1000'],
            'real_diagnosis.root_cause' => ['nullable', 'string', 'max:255'],
            'repair_applied' => ['nullable', 'string', 'max:2000'],
            'validated' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $technicianNotes = $this->input('technician_notes');
        $repairApplied = $this->input('repair_applied');
        $realDiagnosis = (array) $this->input('real_diagnosis', []);
        $actualCauses = collect((array) $this->input('actual_causes', []))
            ->map(static fn ($cause): string => trim(strip_tags((string) $cause)))
            ->filter()
            ->values()
            ->all();

        if (isset($realDiagnosis['summary'])) {
            $realDiagnosis['summary'] = trim(strip_tags((string) $realDiagnosis['summary']));
        }

        if (isset($realDiagnosis['root_cause'])) {
            $realDiagnosis['root_cause'] = trim(strip_tags((string) $realDiagnosis['root_cause']));
        }

        $this->merge([
            'technician_notes' => $technicianNotes !== null
                ? trim(strip_tags((string) $technicianNotes))
                : null,
            'repair_applied' => $repairApplied !== null
                ? trim(strip_tags((string) $repairApplied))
                : null,
            'actual_causes' => $actualCauses,
            'real_diagnosis' => $realDiagnosis === [] ? null : $realDiagnosis,
            'validated' => filter_var($this->input('validated', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        ]);
    }
}
