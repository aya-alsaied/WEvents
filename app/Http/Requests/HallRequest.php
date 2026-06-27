<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HallRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:outside,inside',
            'CapacityOfPeople' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'full_day_price' => 'required|numeric|min:0',
            'hour_price' => 'required|numeric|min:0',
            'information' => 'required|string',
            'rules' => 'required|string',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2024',
            'buffer_minutes' => 'required|integer',
            'status' => 'sometimes|boolean'
        ];
    }
}
