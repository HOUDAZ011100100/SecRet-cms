<?php

namespace App\Http\Requests\EventTasks;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour la création d'une nouvelle tâche associée à un événement.
 *
 * Les tâches sont des éléments internes de la liste de choses à faire pour que les organisateurs et le personnel gèrent la planification de l'événement.
 */
class StoreEventTaskRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - title : Chaîne de caractères requise, max 255 caractères.
     * - description : Chaîne de caractères optionnelle.
     * - due_at : Date optionnelle pour l'échéance de la tâche.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
