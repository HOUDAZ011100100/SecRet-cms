<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de formulaire pour la création d'un nouvel événement.
 *
 * Cette requête gère la création initiale d'un événement, y compris ses
 * informations de base, sa planification, sa capacité et ses images de marque optionnelles.
 */
class StoreEventRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Tout utilisateur authentifié peut tenter de créer un événement,
     * bien que des vérifications de rôle spécifiques puissent être appliquées au niveau du contrôleur ou du service.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - title : Chaîne de caractères requise, max 255 caractères.
     * - description : Chaîne de caractères optionnelle.
     * - location/room : Chaînes de caractères optionnelles, max 255 caractères.
     * - start_at : Date requise.
     * - end_at : Date requise, doit être après start_at.
     * - capacity : Entier positif requis.
     * - ticket_price : Numérique optionnel, min 0.
     * - status : Optionnel, doit être un statut d'événement valide (draft, published, etc.).
     * - image_data : Données d'image encodées en base64 optionnelles.
     * - image_mime : Type MIME de l'image optionnel (jpeg, png, webp, gif).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'capacity' => ['required', 'integer', 'min:1'],
            'ticket_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in([
                Event::STATUS_DRAFT,
                Event::STATUS_PUBLISHED,
                Event::STATUS_CANCELLED,
                Event::STATUS_PENDING_PUBLICATION,
            ])],
            'image_data' => ['nullable', 'string'],
            'image_mime' => ['nullable', 'string', Rule::in([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ])],
        ];
    }
}
