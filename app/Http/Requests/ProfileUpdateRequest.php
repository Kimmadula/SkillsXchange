<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        Log::info('ProfileUpdateRequest rules called', [
            'method' => $this->method(),
            'user_id' => $this->user()?->id,
            'all_data' => $this->all()
        ]);
        
        $userId = $this->user()?->id;
        
        return [
            'username' => ['required', 'string', 'max:50', Rule::unique(User::class)->ignore($userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($userId)],
            'photo' => ['nullable', 'image', 'max:2048'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'bdate' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'firstname' => 'first name',
            'middlename' => 'middle name',
            'lastname' => 'last name',
            'bdate' => 'birth date',
        ];
    }
}
