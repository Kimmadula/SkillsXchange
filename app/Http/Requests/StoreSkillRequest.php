<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Models\Skill;

class StoreSkillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'min:2',
                function ($attribute, $value, $fail) {
                    try {
                        // Normalize the skill name for comparison
                        $normalizedValue = $this->normalizeSkillName($value);
                        
                        // Simple case-insensitive check
                        $existingSkill = Skill::whereRaw('LOWER(name) = ?', [strtolower($normalizedValue)])->first();
                        
                        if ($existingSkill) {
                            $fail('A skill with this name already exists. Please choose a different name.');
                        }
                    } catch (\Exception $e) {
                        // Log the error but don't fail validation to prevent 500 errors
                        Log::warning('Skill validation error: ' . $e->getMessage());
                    }
                },
            ],
            'category' => [
                'required',
                'string',
                'max:50',
                'min:2',
                function ($attribute, $value, $fail) {
                    // Normalize the category name
                    $normalizedValue = $this->normalizeSkillName($value);
                    
                    // Check for double spaces
                    if (strpos($value, '  ') !== false) {
                        $fail('Category name cannot contain double spaces.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Skill name is required.',
            'name.string' => 'Skill name must be a valid text.',
            'name.max' => 'Skill name cannot exceed 50 characters.',
            'name.min' => 'Skill name must be at least 2 characters.',
            'category.required' => 'Category is required.',
            'category.string' => 'Category must be a valid text.',
            'category.max' => 'Category cannot exceed 50 characters.',
            'category.min' => 'Category must be at least 2 characters.',
        ];
    }

    /**
     * Normalize skill name by:
     * - Trimming whitespace
     * - Converting multiple spaces to single space
     * - Converting to proper case (first letter of each word capitalized)
     *
     * @param string $name
     * @return string
     */
    private function normalizeSkillName(string $name): string
    {
        // Trim whitespace
        $name = trim($name);
        
        // Replace multiple spaces with single space
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Convert to proper case (Title Case)
        $name = ucwords(strtolower($name));
        
        return $name;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize the input data before validation
        if ($this->has('name')) {
            $this->merge([
                'name' => $this->normalizeSkillName($this->input('name'))
            ]);
        }
        
        if ($this->has('category')) {
            $this->merge([
                'category' => $this->normalizeSkillName($this->input('category'))
            ]);
        }
    }
}
