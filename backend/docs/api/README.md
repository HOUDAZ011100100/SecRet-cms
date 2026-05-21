# Référence de l'API Backend

VELORA expose une API JSON Laravel Sanctum sous `/api`. Le backend est exclusivement MongoDB et stocke les relations de modèle sous forme de chaînes de caractères Mongo ObjectId dans les champs exposés par l'API.

## Authentification

Points de terminaison publics :

| Méthode | Chemin | Objectif |
| --- | --- | --- |
| `GET` | `/api/health` | Vérification de la disponibilité de MongoDB et Redis. |
| `POST` | `/api/register` | Enregistrer un compte participant ou client. |
| `POST` | `/api/login` | Se connecter et recevoir un jeton porteur (bearer token) Sanctum. |

Les requêtes authentifiées utilisent :

```http
Authorization: Bearer <token>
Accept: application/json
```

Toutes les réponses de `/api/*` sont au format JSON, y compris les erreurs du framework telles que les réponses `401` non authentifiées. Les clients peuvent envoyer un `X-Request-Id` ; les valeurs sûres sont renvoyées, sinon le backend en génère une. Les réponses de l'API incluent les en-têtes de sécurité `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-Permitted-Cross-Domain-Policies` et `Content-Security-Policy`.

`/api/health` reste public pour Docker et les tests de fumée. En production (`APP_DEBUG=false`), les erreurs de dépendances sont volontairement génériques afin de ne pas exposer les détails de connexion MongoDB ou Redis.

Utilisateurs de démonstration après `php artisan migrate:fresh --seed --force` :

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| Admin | `admin@demo.local` | `password` |
| Organisateur | `organisateur@demo.local` | `password` |
| Participant | `participant@demo.local` | `password` |
| Client | `client@demo.local` | `password` |

## Groupes de Routes par Rôle

### Routes Authentifiées Partagées

| Méthode | Chemin | Notes |
| --- | --- | --- |
| `POST` | `/api/logout` | Révoque le jeton actuel. |
| `GET` | `/api/user` | Utilisateur authentifié actuel. |
| `GET` | `/api/notifications` | Notifications paginées de l'utilisateur actuel avec `unread_count` et `meta`. Query optionnelle : `page`, `unread_only`. |
| `GET` | `/api/notifications/unread-count` | Nombre de notifications non lues. |
| `POST` | `/api/notifications/read-all` | Marque toutes les notifications de l'utilisateur actuel comme lues. |
| `POST` | `/api/notifications/{notification}/read` | Marque une notification comme lue. |
| `GET` | `/api/events/browse` | Navigateur d'événements publiés. Recherche optionnelle `q`, max 120 caractères. |
| `GET` | `/api/events/{event}` | Détail de l'événement. Les événements non publiés sont réservés aux gestionnaires. |
| `GET` | `/api/events/{event}/feedbacks` | Commentaires publics, avec une visibilité élargie pour les administrateurs. |

### Participant

| Méthode | Chemin | Notes |
| --- | --- | --- |
| `POST` | `/api/events/{event}/register` | S'inscrire à un événement publié. La capacité et les inscriptions en double sont contrôlées. |
| `GET` | `/api/events/{event}/my-registration` | Inscription du participant pour un événement. |
| `GET` | `/api/my-registrations` | Historique des inscriptions du participant. `payment_status` optionnel : `pending` ou `paid`. |
| `POST` | `/api/registrations/{registration}/pay` | Simulation de paiement pour une inscription en attente. |
| `DELETE` | `/api/registrations/{registration}` | Annuler une inscription non payée. |
| `GET` | `/api/registrations/{registration}/ticket` | Renvoie le contenu du ticket pour une inscription payée. |
| `POST` | `/api/events/{event}/feedback` | Soumettre un commentaire pour un événement payé auquel on a assisté. |

### Organisateur

| Méthode | Chemin | Notes |
| --- | --- | --- |
| `GET` | `/api/organizer/events` | Événements possédés ou créés par l'organisateur. |
| `POST` | `/api/organizer/events` | Créer un projet d'événement (draft). |
| `PATCH` | `/api/organizer/events/{event}` | Mettre à jour l'événement géré. |
| `PATCH` | `/api/organizer/events/{event}/capacity` | Mettre à jour la capacité, jamais en dessous du nombre d'inscrits. |
| `POST` | `/api/organizer/events/{event}/request-publication` | Soumettre l'événement pour approbation par un administrateur. |
| `GET` | `/api/organizer/events/{event}/tasks` | Lister les tâches de planification. |
| `POST` | `/api/organizer/events/{event}/tasks` | Créer une tâche de planification. |
| `PATCH` | `/api/organizer/events/{event}/tasks/{eventTask}` | Mettre à jour une tâche de planification. |
| `DELETE` | `/api/organizer/events/{event}/tasks/{eventTask}` | Supprimer une tâche de planification. |
| `GET` | `/api/organizer/events/{event}/activities` | Lister les activités de l'événement. |
| `POST` | `/api/organizer/events/{event}/activities` | Créer une activité d'événement. |
| `PATCH` | `/api/organizer/events/{event}/activities/{eventActivity}` | Mettre à jour une activité d'événement. |
| `DELETE` | `/api/organizer/events/{event}/activities/{eventActivity}` | Supprimer une activité d'événement. |
| `GET` | `/api/organizer/registrations/events` | Événements avec le nombre d'inscriptions. |
| `GET` | `/api/organizer/registrations` | Inscriptions pour les événements gérés par l'organisateur. |
| `DELETE` | `/api/organizer/registrations/{registration}` | Supprimer une inscription non payée pour un événement géré. |

### Administrateur

Les administrateurs peuvent utiliser les routes de planification d'événements des organisateurs et disposent également de ces routes réservées aux administrateurs :

| Méthode | Chemin | Notes |
| --- | --- | --- |
| `GET` | `/api/admin/events` | Tous les événements. Recherche optionnelle `q`, max 120 caractères. |
| `GET` | `/api/admin/organizer-events` | Événements possédés ou créés par des organisateurs. |
| `GET` | `/api/admin/my-events` | Événements assignés à ou créés par l'administrateur. |
| `DELETE` | `/api/admin/events/{event}` | Supprimer un événement. |
| `PATCH` | `/api/admin/events/{event}/assign-organizer` | Assigner un responsable d'événement. Le service accepte un utilisateur `organizer` ou `admin`. |
| `PATCH` | `/api/admin/events/{event}` | Mettre à jour un événement. |
| `PATCH` | `/api/admin/events/{event}/capacity` | Mettre à jour la capacité. |
| `POST` | `/api/admin/events/{event}/approve-publication` | Publier un événement en attente. |
| `GET` | `/api/admin/event-requests` | Liste des demandes d'événements des clients. `status` optionnel : `pending`, `approved`, ou `rejected`. |
| `POST` | `/api/admin/event-requests/{eventRequest}/review` | Approuver ou rejeter une demande d'événement. |
| `GET` | `/api/admin/users` | Liste des utilisateurs. `role` optionnel : `admin`, `organizer`, `participant`, ou `client`. |
| `GET` | `/api/admin/organizers` | Liste des organisateurs. |
| `POST` | `/api/admin/users` | Créer un utilisateur. |
| `PATCH` | `/api/admin/users/{user}` | Mettre à jour un utilisateur. |
| `DELETE` | `/api/admin/users/{user}` | Supprimer un utilisateur. L'auto-suppression est bloquée. |
| `GET` | `/api/admin/stats` | Métriques du tableau de bord administrateur, mises en cache brièvement côté backend. |
| `GET` | `/api/admin/registrations/events` | Événements avec le nombre d'inscriptions. |
| `GET` | `/api/admin/registrations` | Liste de gestion des inscriptions. |
| `DELETE` | `/api/admin/registrations/{registration}` | Supprimer une inscription non payée. |
| `POST` | `/api/admin/feedbacks/{feedback}/approve` | Approuver un commentaire. |
| `DELETE` | `/api/admin/feedbacks/{feedback}` | Supprimer un commentaire. |

### Client

| Méthode | Chemin | Notes |
| --- | --- | --- |
| `POST` | `/api/event-requests` | Soumettre une demande d'événement. Une demande en attente ou un événement actif bloque toute nouvelle soumission. |
| `DELETE` | `/api/event-requests/{eventRequest}` | Supprimer sa propre demande en attente. |
| `GET` | `/api/client/stats` | Données du tableau de bord client, groupes de demandes, listes d'événements et revenus. |

## Argent et Dates

- L'argent est stocké en interne en centimes entiers (`amount_cents`, `ticket_price_cents`).
- Les champs de compatibilité de l'API restent des chaînes ou des nombres compatibles avec les décimales, tels que `amount` et `ticket_price`.
- Les dates doivent être envoyées sous forme de chaînes ISO-8601.
- Mongo stocke les dates sous forme de dates BSON via les casts Laravel.

## Format des Erreurs

Les erreurs de validation utilisent la réponse JSON `422` par défaut de Laravel. Les erreurs de domaine utilisent :

```json
{
  "message": "Message d'erreur lisible par l'homme."
}
```

La connexion et l'inscription sont limitées en débit (rate limited). Les réponses de limite de débit utilisent HTTP `429` avec la même enveloppe de message.

Le contrat OpenAPI dans `openapi.yaml` documente l'inventaire des routes et les principaux schémas de requête/réponse. Il est intentionnellement orienté vers le frontend ; la documentation de classe générée reste disponible pour les détails de l'implémentation PHP interne.
