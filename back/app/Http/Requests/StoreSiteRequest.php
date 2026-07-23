<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom'       => ['required', 'string', 'max:255'],
            'url'       => ['required', 'url', 'max:255'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
