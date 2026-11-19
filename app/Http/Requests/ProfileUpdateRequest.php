<?php

namespace App\Http\Requests;

use App\Models\Desa;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $kecamatanBinaan = $this->input('kecamatan_binaan');
        $kecamatanKey = Desa::normalizeKecamatan(is_string($kecamatanBinaan) ? $kecamatanBinaan : '');

        $rules = [
            'update_work_area' => ['sometimes', 'boolean'],
            'nip' => [
                'nullable',
                Rule::requiredIf($this->boolean('update_work_area')),
                'string',
                'max:50',
                Rule::unique(User::class, 'nip')->ignore($this->user()->id),
            ],
            'pangkat_golongan' => ['nullable', Rule::requiredIf($this->boolean('update_work_area')), 'string', 'max:100'],
            'jabatan' => ['nullable', Rule::requiredIf($this->boolean('update_work_area')), 'string', 'max:150'],
            'unit_kerja' => ['nullable', Rule::requiredIf($this->boolean('update_work_area')), 'string', 'max:255'],
            'kecamatan_binaan' => [
                'nullable',
                Rule::requiredIf($this->boolean('update_work_area')),
                'string',
                'max:255',
                'exists:desas,kecamatan',
            ],
            'desa_binaan' => [
                Rule::requiredIf($this->boolean('update_work_area')),
                'array',
                'min:1',
            ],
            'desa_binaan.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('desas', 'id')->where(function (Builder $query) use ($kecamatanKey): void {
                    $query->whereRaw("UPPER(REPLACE(kecamatan, ' ', '')) = ?", [$kecamatanKey]);
                }),
            ],
        ];

        if (! $this->boolean('update_work_area')) {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['email'] = [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ];
        }

        if ($this->user()?->isAdmin()) {
            $rules['wilayah_tanggung_jawab'] = ['nullable', 'string', 'max:255'];
            $rules['no_telepon'] = ['nullable', 'string', 'max:50'];
        }

        return $rules;
    }
}
