<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
        {
            $data = [];

            if ($this->has('name')) {
                $data['name'] = trim($this->input('name'));
            }

            if ($this->has('email')) {
                $data['email'] = strtolower(trim($this->input('email')));
            }

            if ($this->has('phone')) {
                $data['phone'] = trim($this->input('phone'));
            }

            if ($this->has('address')) {
                $data['address'] = trim($this->input('address'));
            }

            $this->merge($data);
        }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
