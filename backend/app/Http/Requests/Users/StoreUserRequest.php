<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Requête de formulaire pour que les administrateurs créent un nouvel utilisateur manuellement.
 *
 * Contrairement à l'inscription publique, cela permet d'assigner n'importe quel rôle système (Admin, Organisateur, etc.).
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - name : Chaîne de caractères requise, max 255.
     * - email : Format email requis.
     * - password : Chaîne de caractères requise, suit les paramètres de sécurité par défaut.
     * - role : Requis, doit être un rôle système valide.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_ORGANIZER,
                User::ROLE_PARTICIPANT,
                User::ROLE_CLIENT,
            ])],
        ];
    }
}
