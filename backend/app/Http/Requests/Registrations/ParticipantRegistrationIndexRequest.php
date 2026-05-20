<?php

namespace App\Http\Requests\Registrations;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantRegistrationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->getAttribute('role') === User::ROLE_PARTICIPANT;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'paid'])],
        ];
    }
}
