<?php

namespace App\Http\Requests\EventTasks;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour la mise à jour d'une tâche d'événement existante.
 *
 * Utilisé pour modifier les détails de la tâche ou les marquer comme terminées.
 */
class UpdateEventTaskRequest extends FormRequest
{
    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - title : Chaîne de caractères optionnelle, max 255 caractères.
     * - description : Chaîne de caractères optionnelle.
     * - is_done : Booléen optionnel pour basculer l'état d'accomplissement de la tâche.
     * - due_at : Date optionnelle pour la mise à jour de l'échéance.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_done' => ['sometimes', 'boolean'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
