<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Requête de formulaire pour mettre à jour les informations d'un utilisateur existant.
 *
 * Prend en charge les mises à jour partielles du nom, de l'email, du mot de passe et du rôle.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Les règles utilisent 'sometimes' pour permettre des mises à jour partielles :
     * - email : Email valide si fourni.
     * - password : Optionnel, doit respecter les paramètres de sécurité par défaut si fourni.
     * - role : Optionnel, doit être un rôle système valide si fourni.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
            'password' => ['nullable', Password::defaults()],
            'role' => ['sometimes', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_ORGANIZER,
                User::ROLE_PARTICIPANT,
                User::ROLE_CLIENT,
            ])],
        ];
    }
}
