<?php

namespace App\Http\Requests\Feedbacks;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour soumettre un commentaire pour un événement.
 *
 * Cette requête garantit que seuls les participants peuvent soumettre des commentaires
 * et valide que la note et les commentaires respectent des normes spécifiques.
 */
class StoreFeedbackRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Seuls les utilisateurs ayant le rôle 'PARTICIPANT' sont autorisés à soumettre des commentaires.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->getAttribute('role') === User::ROLE_PARTICIPANT;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - rating : Entier requis entre 1 et 5.
     * - comment : Chaîne de caractères optionnelle, maximum 2000 caractères.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
