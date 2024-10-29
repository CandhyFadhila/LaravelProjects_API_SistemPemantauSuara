<?php

namespace App\Http\Requests\Paslon;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePaslonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'partai_id' => 'required|integer|exists:partais,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama pasangan calon tidak boleh kosong.',
            'nama.string' => 'Nama pasangan calon harus berupa teks.',
            'nama.max' => 'Nama pasangan calon tidak boleh lebih dari 255 karakter.',
            'partai_id.required' => 'Silahkan masukkan partai dari paslon terlebih dahulu.',
            'partai_id.integer' => 'Partai dari paslon harus berupa angka.',
            'partai_id.exists' => 'Partai yang dipilih tidak ditemukan di database.'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $response = [
            'status' => Response::HTTP_BAD_REQUEST,
            'message' => $validator->errors()
        ];

        throw new HttpResponseException(response()->json($response, Response::HTTP_BAD_REQUEST));
    }
}
