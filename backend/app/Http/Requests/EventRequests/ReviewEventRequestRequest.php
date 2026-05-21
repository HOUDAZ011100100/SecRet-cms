<?php

namespace App\Http\Requests\EventRequests;

use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de formulaire pour que les administrateurs révisent et décident d'une demande d'événement.
 *
 * Les administrateurs peuvent soit approuver soit rejeter la proposition d'un client.
 */
class ReviewEventRequestRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Seuls les administrateurs sont autorisés à réviser et à décider des demandes d'événements.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - decision : Requis, doit être 'approved' ou 'rejected'.
     * - rejection_reason : Requis uniquement si la décision est 'rejected'.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([
                EventRequest::STATUS_APPROVED,
                EventRequest::STATUS_REJECTED,
            ])],
            'rejection_reason' => ['required_if:decision,'.EventRequest::STATUS_REJECTED, 'nullable', 'string'],
        ];
    }
}
