<?php

namespace App\Http\Requests\Counting;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreQuickCountRequest extends FormRequest
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
            // 'pasangan_calon_id' => 'required|integer|exists:pasangan_calons,id',
            // 'periode' => 'required|integer',
            // 'kategori_suara_id' => 'required|integer|exists:kategori_suaras,id',
            'kelurahan_id' => 'nullable|integer|exists:kelurahans,id',
            'tps' => 'nullable|integer',
            'jumlah_suara' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'periode.required' => 'Periode suara tidak diperbolehkan kosong.',
            'periode.integer' => 'Periode suara tidak diperbolehkan mengandung selain angka.',
            'jumlah_suara.required' => 'Jumlah suara tidak diperbolehkan kosong.',
            'jumlah_suara.integer' => 'Jumlah suara tidak diperbolehkan mengandung selain angka.',
            'tps.required' => 'TPS tidak diperbolehkan kosong.',
            'tps.integer' => 'TPS tidak diperbolehkan mengandung selain angka.',
            'pasangan_calon_id.required' => 'Nama pasangan calon tidak diperbolehkan kosong.',
            'pasangan_calon_id.integer' => 'Nama pasangan calon tidak diperbolehkan mengandung selain angka.',
            'pasangan_calon_id.exists' => 'Nama pasangan calon tersebut tidak ada dalam database.',
            'kelurahan_id.required' => 'Nama kelurahan tidak diperbolehkan kosong.',
            'kelurahan_id.integer' => 'Nama kelurahan tidak diperbolehkan mengandung selain angka.',
            'kelurahan_id.exists' => 'Nama kelurahan tersebut tidak ada dalam database.',
            'kategori_suara_id.required' => 'Kategori suara tidak diperbolehkan kosong.',
            'kategori_suara_id.integer' => 'Kategori suara tidak diperbolehkan mengandung selain angka.',
            'kategori_suara_id.exists' => 'Kategori suara tersebut tidak ada dalam database.',
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
