<?php

namespace App\Services;

use App\Exceptions\RegistrationException;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MongoDB\Driver\Exception\BulkWriteException;

/**
 * Service gérant le cycle de vie des inscriptions des participants aux événements.
 *
 * Ce service gère la création des inscriptions, le traitement des paiements et les annulations.
 * Les règles importantes résident ici car elles doivent rester identiques quel que soit le
 * contrôleur ou le flux de travail du personnel qui les déclenche :
 * - événements publiés uniquement ;
 * - pas de surréservation ;
 * - une seule inscription par participant et par événement ;
 * - les inscriptions payées ne peuvent pas être annulées ;
 * - l'argent est persisté dans le modèle sous forme de centimes entiers.
 */
class RegistrationService
{
    /**
     * Inscrit un participant à un événement.
     *
     * @param  User  $participant  L'utilisateur qui souhaite s'inscrire.
     * @param  Event  $event  L'événement auquel il s'inscrit.
     * @return Registration La nouvelle inscription créée.
     *
     * @throws RegistrationException Si l'événement est fermé, complet ou si l'utilisateur est déjà inscrit.
     */
    public function register(User $participant, Event $event): Registration
    {
        // Les participants ne peuvent s'inscrire qu'à des événements déjà visibles et actifs.
        if ($event->status !== Event::STATUS_PUBLISHED) {
            throw new RegistrationException('Événement non ouvert aux inscriptions.');
        }

        try {
            return DB::transaction(function () use ($participant, $event) {
                // Recharger l'événement à l'intérieur de la transaction pour que les vérifications de capacité
                // utilisent l'état le plus récent du document.
                $freshEvent = Event::query()->whereKey($event->id)->firstOrFail();

                if ((int) $freshEvent->registered_count >= (int) $freshEvent->capacity) {
                    throw new RegistrationException('Événement complet.');
                }

                // Vérification préalable conviviale pour les doublons ; l'index unique Mongo reste la garantie finale.
                $existing = Registration::query()
                    ->where('event_id', $freshEvent->id)
                    ->where('user_id', $participant->id)
                    ->first();

                if ($existing) {
                    throw new RegistrationException('Déjà inscrit.', registration: $existing);
                }

                // L'incrémentation conditionnelle est le garde-fou contre la surréservation lors d'inscriptions simultanées.
                // Si une autre requête remplit l'événement en premier, cette mise à jour n'affecte aucun document.
                $incremented = Event::query()
                    ->whereKey($freshEvent->id)
                    ->where('registered_count', '<', (int) $freshEvent->capacity)
                    ->increment('registered_count');

                if (! $incremented) {
                    throw new RegistrationException('Événement complet.');
                }

                $amountCents = Money::toCents($freshEvent->ticket_price);
                $isFree = $amountCents <= 0;

                // Le modèle accepte le montant décimal compatible avec l'API et persiste amount_cents.
                $registration = Registration::create([
                    'event_id' => $freshEvent->id,
                    'user_id' => $participant->id,
                    'status' => 'registered',
                    'payment_status' => $isFree ? 'paid' : 'pending',
                    'ticket_code' => $this->uniqueTicketCode(),
                    'amount' => $freshEvent->ticket_price,
                    'paid_at' => $isFree ? now() : null,
                    'registered_at' => now(),
                ]);

                // Les événements gratuits reçoivent tout de même un enregistrement de paiement complété
                // afin que les statistiques et les règles relatives aux tickets restent uniformes.
                if ($isFree) {
                    Payment::create([
                        'registration_id' => $registration->id,
                        'amount' => 0,
                        'currency' => 'EUR',
                        'status' => 'completed',
                        'method' => 'free',
                        'meta' => ['note' => 'Gratuit'],
                    ]);
                }

                $registration->load('event', 'user');
                // Les tableaux de bord du personnel sont basés sur les notifications, donc une inscription réussie est diffusée ici.
                NotificationService::participantRegistered($registration);

                return $registration;
            });
        } catch (BulkWriteException $exception) {
            // Convertir un conflit de clé unique Mongo en la même erreur de domaine que la pré-vérification.
            $this->throwDuplicateRegistrationIfNeeded($exception, $participant, $event);

            throw $exception;
        }
    }

    /**
     * Traite un paiement pour une inscription en attente.
     *
     * @throws RegistrationException Si l'inscription est déjà payée.
     */
    public function pay(Registration $registration): Registration
    {
        if ($registration->payment_status === 'paid') {
            throw new RegistrationException('Déjà payé.', 200, $registration);
        }

        return DB::transaction(function () use ($registration) {
            $amount = $registration->amount;

            // Seule une inscription en attente peut passer à l'état payé, ce qui évite les doublons de paiement.
            $updated = Registration::query()
                ->whereKey($registration->id)
                ->where('payment_status', 'pending')
                ->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

            if (! $updated) {
                $registration->refresh();
                throw new RegistrationException('Déjà payé.', 200, $registration);
            }

            // Il s'agit d'une entrée dans le registre des paiements simulés, mais elle suit le même stockage d'argent basé sur les centimes.
            Payment::create([
                'registration_id' => $registration->id,
                'amount' => $amount,
                'currency' => 'EUR',
                'status' => 'completed',
                'method' => 'card_mock', // Méthode de paiement simulée.
                'meta' => ['simulated' => true],
            ]);

            $registration->refresh();
            $registration->load([
                'event',
                'event.eventRequest',
                'user',
            ]);

            // La finalisation du paiement peut débloquer les billets et le reporting opérationnel.
            NotificationService::participantPaid($registration);

            return $registration;
        });
    }

    /**
     * Annule une inscription en attente.
     * Seules les inscriptions non payées peuvent être annulées via cette méthode.
     *
     * @throws RegistrationException Si l'inscription est déjà payée.
     */
    public function cancel(Registration $registration): void
    {
        if ($registration->payment_status === 'paid') {
            throw new RegistrationException('Impossible d\'annuler une inscription déjà payée.');
        }

        DB::transaction(function () use ($registration) {
            // Supprimer uniquement si le document est toujours en attente ; un paiement simultané l'emporte sur l'annulation.
            $deleted = Registration::query()
                ->whereKey($registration->id)
                ->where('payment_status', 'pending')
                ->delete();

            if (! $deleted) {
                throw new RegistrationException('Impossible d\'annuler une inscription déjà payée.');
            }

            // Maintenir la cohérence du compteur d'événements dénormalisé avec l'inscription supprimée.
            Event::query()
                ->whereKey($registration->event_id)
                ->where('registered_count', '>', 0)
                ->decrement('registered_count');
        });
    }

    private function uniqueTicketCode(): string
    {
        // Les collisions d'UUID sont extrêmement peu probables, mais l'index unique permet de les détecter.
        // Quelques tentatives permettent de garder une réponse API propre sans masquer un problème de stockage persistant.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $ticketCode = (string) Str::uuid();

            if (! Registration::query()->where('ticket_code', $ticketCode)->exists()) {
                return $ticketCode;
            }
        }

        throw new RegistrationException('Impossible de générer un billet unique.');
    }

    /**
     * Gère le cas où deux requêtes passent la pré-vérification de doublon avant que l'une ne remporte l'index unique.
     *
     * @throws RegistrationException
     */
    private function throwDuplicateRegistrationIfNeeded(BulkWriteException $exception, User $participant, Event $event): void
    {
        if (! $this->isDuplicateKey($exception) || ! $this->isRegistrationUniquenessConflict($exception)) {
            return;
        }

        $existing = Registration::query()
            ->where('event_id', $event->id)
            ->where('user_id', $participant->id)
            ->first();

        if (! $existing) {
            return;
        }

        throw new RegistrationException('Déjà inscrit.', registration: $existing);
    }

    private function isDuplicateKey(BulkWriteException $exception): bool
    {
        return str_contains($exception->getMessage(), 'duplicate key')
            || str_contains($exception->getMessage(), 'E11000');
    }

    private function isRegistrationUniquenessConflict(BulkWriteException $exception): bool
    {
        return str_contains($exception->getMessage(), 'registrations_event_user_unique');
    }
}
