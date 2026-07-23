<?php

// app/Http/Requests/StoreUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom'        => ['required', 'string', 'max:255'],
            'prenom'     => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8'],
            'role'       => ['required', 'string', 'in:client,technicien,administrateur'],
            'telephone'  => ['nullable', 'string', 'max:20', 'required_if:role,client'],
            'specialite' => ['nullable', 'string', 'max:255', 'required_if:role,technicien'],
        ];
    }
}
