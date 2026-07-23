<?php

// app/Http/Requests/UpdateUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;

        return [
            'nom'        => ['sometimes', 'string', 'max:255'],
            'prenom'     => ['sometimes', 'string', 'max:255'],
            'email'      => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'   => ['nullable', 'string', 'min:8'],
            'role'       => ['sometimes', 'string', 'in:client,technicien,administrateur'],
            'telephone'  => ['nullable', 'string', 'max:20'],
            'specialite' => ['nullable', 'string', 'max:255'],
        ];
    }
}
