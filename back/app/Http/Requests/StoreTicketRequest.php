<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priorite'    => ['required', 'string', 'in:basse,normale,urgente'],
            'site_id'     => ['required', 'integer', 'exists:sites,id'],
        ];
    }
}
