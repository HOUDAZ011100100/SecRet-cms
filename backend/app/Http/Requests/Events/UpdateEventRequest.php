<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de formulaire pour la mise à jour d'un événement existant.
 *
 * Cette requête gère les mises à jour partielles des détails de l'événement. Tous les champs sont optionnels
 * mais doivent suivre les règles spécifiées s'ils sont fournis.
 */
class UpdateEventRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Les utilisateurs authentifiés peuvent tenter cela ; les permissions spécifiques
     * (ex : propriétaire de l'événement ou administrateur) sont vérifiées dans la logique métier.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Les règles utilisent 'sometimes' pour permettre des mises à jour partielles :
     * - title : Chaîne de caractères, max 255.
     * - start_at : Date valide.
     * - end_at : Date valide après start_at.
     * - capacity : Entier >= 1.
     * - status : Doit être un statut d'événement valide (draft, published, completed, etc.).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:255'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date', 'after:start_at'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'ticket_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in([
                Event::STATUS_DRAFT,
                Event::STATUS_PUBLISHED,
                Event::STATUS_COMPLETED,
                Event::STATUS_CANCELLED,
                Event::STATUS_PENDING_PUBLICATION,
            ])],
        ];
    }
}
