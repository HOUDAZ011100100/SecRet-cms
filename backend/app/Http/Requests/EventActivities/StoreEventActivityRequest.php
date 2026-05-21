<?php

namespace App\Http\Requests\EventActivities;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour la création d'une nouvelle activité d'événement (élément du programme).
 *
 * Les activités représentent des créneaux horaires ou des sessions spécifiques au sein d'un événement plus large.
 */
class StoreEventActivityRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - title : Chaîne de caractères requise, max 255 caractères.
     * - description : Chaîne de caractères optionnelle.
     * - starts_at : Date optionnelle.
     * - ends_at : Date optionnelle, doit être égale ou postérieure à starts_at si fournie.
     * - sort_order : Entier optionnel, utilisé pour afficher les activités dans un ordre spécifique.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
