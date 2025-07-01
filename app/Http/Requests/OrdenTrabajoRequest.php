<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrdenTrabajoRequest extends FormRequest
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
            'cliente_id' => 'required|exists:clientes,id',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'empleado_id' => 'required|exists:empleados,id',
            'servicio_id' => 'required|exists:servicios,id',
            'description' => 'required|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'total_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date'
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
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'vehiculo_id.required' => 'El vehículo es obligatorio.',
            'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
            'empleado_id.required' => 'El empleado es obligatorio.',
            'empleado_id.exists' => 'El empleado seleccionado no existe.',
            'servicio_id.required' => 'El servicio es obligatorio.',
            'servicio_id.exists' => 'El servicio seleccionado no existe.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser: pendiente, en progreso, completada o cancelada.',
            'total_amount.required' => 'El monto total es obligatorio.',
            'total_amount.numeric' => 'El monto total debe ser un valor numérico.',
            'total_amount.min' => 'El monto total debe ser mayor o igual a 0.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.date' => 'La fecha de finalización debe ser una fecha válida.',
            'end_date.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.'
        ];
    }
}
