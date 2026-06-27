<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicPartyRequest extends FormRequest
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
            'information' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:g:i A',
            'end_time' => 'required|date_format:g:i A|after:start_time',
            'location' => 'required|string|max:255',
            'price' => 'required',
            'image' => 'required|mimes:jpg,jpeg,png,webp|max:2024',
            'tickets' => 'required|integer',
            'status' => 'sometimes|boolean'
        ];
    }
}
