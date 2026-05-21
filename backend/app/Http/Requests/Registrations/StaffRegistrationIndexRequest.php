<?php

namespace App\Http\Requests\Registrations;

use App\Rules\MongoObjectId;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour lister et filtrer les inscriptions par les membres du personnel.
 *
 * Cette requête fournit des capacités de filtrage pour que le personnel puisse visualiser les inscriptions des participants,
 * permettant la recherche d'événements spécifiques, le filtrage par statut de paiement et la recherche par mots-clés.
 */
class StaffRegistrationIndexRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Les utilisateurs authentifiés (généralement le personnel/administrateur) peuvent effectuer cette requête.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - event_id : Optionnel, doit être un ObjectId MongoDB valide.
     * - payment_status : Optionnel, parmi 'pending', 'paid', ou 'all'.
     * - q : Chaîne de recherche optionnelle, max 120 caractères.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['nullable', 'string', new MongoObjectId],
            'payment_status' => ['nullable', 'in:pending,paid,all'],
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }
}
