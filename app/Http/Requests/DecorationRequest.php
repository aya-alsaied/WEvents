<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecorationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'information' => 'required|string',
            'location' => 'required|string|max:255',
            'price' => 'required|numeric',

            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'occasion_ids' => 'required|array|min:1',
            'occasion_ids.*' => 'exists:occasions,id',

            'status' => 'sometimes|boolean',
        ];
    }
}
