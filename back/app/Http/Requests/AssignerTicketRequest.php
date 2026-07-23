<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignerTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'technicien_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
