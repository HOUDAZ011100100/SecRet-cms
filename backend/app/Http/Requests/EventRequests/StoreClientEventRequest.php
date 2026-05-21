<?php

namespace App\Http\Requests\EventRequests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de formulaire pour que les clients soumettent une proposition/demande d'événement.
 *
 * Cette requête gère la soumission initiale d'une idée d'événement par un client,
 * y compris les informations de contact et la planification souhaitée.
 */
class StoreClientEventRequest extends FormRequest
{
    /**
     * Préparer les données pour la validation.
     *
     * Remplit automatiquement les informations de contact à partir de l'utilisateur authentifié
     * si elles ne sont pas explicitement fournies dans la requête.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        $this->merge([
            'preferred_start' => $this->input('preferred_start') ?: null,
            'preferred_end' => $this->input('preferred_end') ?: null,
            'contact_phone' => $this->input('contact_phone') ?: null,
            'contact_email' => $user instanceof User ? $user->getAttribute('email') : $this->input('contact_email'),
            'contact_name' => $this->input('contact_name') ?: ($user instanceof User ? $user->getAttribute('name') : null),
        ]);
    }

    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * Seuls les utilisateurs ayant le rôle 'CLIENT' sont autorisés à soumettre des demandes d'événements.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->getAttribute('role') === User::ROLE_CLIENT;
    }

    /**
     * Obtenir les règles de validation qui s'appliquent à la requête.
     *
     * Règles :
     * - title : Chaîne de caractères requise, max 255.
     * - ticket_price : Numérique requis, min 0.
     * - contact_name/email : Requis pour la communication.
     * - image/image_data : Prise en charge des téléchargements de fichiers binaires et des images encodées en base64.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'preferred_start' => ['nullable', 'date'],
            'preferred_end' => ['nullable', 'date', 'after_or_equal:preferred_start'],
            'location' => ['nullable', 'string', 'max:255'],
            'ticket_price' => ['required', 'numeric', 'min:0'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
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
