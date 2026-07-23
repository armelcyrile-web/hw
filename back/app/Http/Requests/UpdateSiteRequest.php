<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nom'       => ['sometimes', 'string', 'max:255'],
            'url'       => ['sometimes', 'url', 'max:255'],
            'client_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
