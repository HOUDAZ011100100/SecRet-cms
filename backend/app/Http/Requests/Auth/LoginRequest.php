<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour l'authentification de l'utilisateur (Connexion).
 *
 * Valide les identifiants requis pour établir une session ou émettre un jeton.
 */
class LoginRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - email : Requis et doit être au format email valide.
     * - password : Chaîne de caractères requise.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }
}
