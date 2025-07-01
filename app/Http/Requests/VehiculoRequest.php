<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class VehiculoRequest extends FormRequest
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
        $vehiculoId = Route::current()->parameter('vehiculo');
        $currentYear = date('Y');
        
        return [
            'cliente_id' => 'required|exists:clientes,id',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . $currentYear,
            'license_plate' => 'required|string|unique:vehiculos,license_plate' . ($vehiculoId ? ',' . $vehiculoId : ''),
            'vin' => 'required|string|unique:vehiculos,vin' . ($vehiculoId ? ',' . $vehiculoId : ''),
            'color' => 'required|string',
            'status' => 'boolean'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $currentYear = date('Y');
        
        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'brand.required' => 'La marca es obligatoria.',
            'brand.string' => 'La marca debe ser una cadena de texto.',
            'brand.max' => 'La marca no puede tener más de 255 caracteres.',
            'model.required' => 'El modelo es obligatorio.',
            'model.string' => 'El modelo debe ser una cadena de texto.',
            'model.max' => 'El modelo no puede tener más de 255 caracteres.',
            'year.required' => 'El año es obligatorio.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.min' => 'El año debe ser mayor o igual a 1900.',
            'year.max' => 'El año no puede ser mayor a ' . $currentYear . '.',
            'license_plate.required' => 'La placa es obligatoria.',
            'license_plate.string' => 'La placa debe ser una cadena de texto.',
            'license_plate.unique' => 'Esta placa ya está registrada.',
            'vin.required' => 'El VIN es obligatorio.',
            'vin.string' => 'El VIN debe ser una cadena de texto.',
            'vin.unique' => 'Este VIN ya está registrado.',
            'color.required' => 'El color es obligatorio.',
            'color.string' => 'El color debe ser una cadena de texto.',
            'status.boolean' => 'El estado debe ser verdadero o falso.'
        ];
    }
}
