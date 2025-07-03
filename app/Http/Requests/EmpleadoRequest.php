<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class EmpleadoRequest extends FormRequest
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
        $empleado = Route::current()->parameter('empleado');
        $empleadoId = $empleado ? $empleado->id : null;
        
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:empleados,email' . ($empleadoId ? ',' . $empleadoId : ''),
            'phone' => 'required|string|max:20',
            'position' => 'required|string|max:100',
            'salary' => 'required|numeric|min:1|max:999999.99',
            'hire_date' => 'required|date|before_or_equal:today',
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
            'position.required' => 'El cargo es obligatorio.',
            'position.string' => 'El cargo debe ser una cadena de texto.',
            'salary.required' => 'El salario es obligatorio.',
            'salary.numeric' => 'El salario debe ser un valor numérico.',
            'salary.min' => 'El salario debe ser mayor o igual a 0.',
            'hire_date.required' => 'La fecha de contratación es obligatoria.',
            'hire_date.date' => 'La fecha de contratación debe ser una fecha válida.',
            'status.boolean' => 'El estado debe ser verdadero o falso.'
        ];
    }
}
