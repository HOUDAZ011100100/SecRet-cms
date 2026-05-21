<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Requête de formulaire pour l'inscription d'un nouvel utilisateur.
 *
 * Gère la validation des données pour les nouveaux comptes publics (Participant ou Client).
 */
class RegisterRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - name : Chaîne de caractères requise, max 255.
     * - email : Requis, format email unique.
     * - password : Requis, doit correspondre à la confirmation, suit les paramètres de sécurité par défaut.
     * - role : Requis, doit être soit PARTICIPANT, soit CLIENT.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([
                User::ROLE_PARTICIPANT,
                User::ROLE_CLIENT,
            ])],
        ];
    }

    /**
     * Obtenir les messages d'erreur personnalisés pour les échecs de validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
        ];
    }
}
