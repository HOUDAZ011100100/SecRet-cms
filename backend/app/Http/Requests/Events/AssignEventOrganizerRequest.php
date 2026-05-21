<?php

namespace App\Http\Requests\Events;

use App\Rules\MongoObjectId;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour l'assignation d'un organisateur à un événement.
 *
 * Cette requête gère la validation et l'autorisation pour la mise à jour
 * de l'organisateur principal responsable d'un événement spécifique.
 */
class AssignEventOrganizerRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Seuls les utilisateurs disposant de privilèges administratifs sont autorisés à réassigner les organisateurs d'événements.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - organizer_id : Requis, doit être une chaîne ObjectId MongoDB valide.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organizer_id' => ['required', 'string', new MongoObjectId],
        ];
    }
}
