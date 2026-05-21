<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de formulaire pour mettre à jour uniquement la capacité d'un événement.
 *
 * Cette requête est utilisée pour des mises à jour ciblées de la capacité maximale d'un événement,
 * souvent utilisée par les organisateurs pour ajuster les limites de participation.
 */
class UpdateEventCapacityRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Les utilisateurs authentifiés peuvent tenter cela ; les vérifications de propriété/rôle
     * sont généralement gérées dans la couche contrôleur ou service.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - capacity : Requis, doit être un entier d'au moins 1.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'capacity' => ['required', 'integer', 'min:1'],
        ];
    }
}
