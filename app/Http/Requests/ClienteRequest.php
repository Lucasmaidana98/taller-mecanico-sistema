<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class ClienteRequest extends FormRequest
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
        $cliente = Route::current()->parameter('cliente');
        $clienteId = $cliente ? $cliente->id : null;
        
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clientes,email' . ($clienteId ? ',' . $clienteId : ''),
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'document_number' => 'required|string|max:20|unique:clientes,document_number' . ($clienteId ? ',' . $clienteId : ''),
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
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'address.required' => 'La dirección es obligatoria.',
            'address.string' => 'La dirección debe ser una cadena de texto.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.string' => 'El número de documento debe ser una cadena de texto.',
            'document_number.unique' => 'Este número de documento ya está registrado.',
            'status.boolean' => 'El estado debe ser verdadero o falso.'
        ];
    }
}
