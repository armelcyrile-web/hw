<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResoudreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'commentaire' => ['sometimes', 'string', 'max:1000'],
        ];
    }
}
