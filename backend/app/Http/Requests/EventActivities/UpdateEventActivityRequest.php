<?php

namespace App\Http\Requests\EventActivities;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour la mise à jour d'une activité d'événement existante.
 *
 * Permet des mises à jour partielles des détails de l'activité, y compris le titre, les horaires et l'ordre.
 */
class UpdateEventActivityRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - title : Chaîne de caractères optionnelle (si fournie), max 255 caractères.
     * - description : Chaîne de caractères optionnelle.
     * - starts_at/ends_at : Dates optionnelles, ends_at doit être après starts_at.
     * - sort_order : Entier optionnel pour l'ordre d'affichage.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
