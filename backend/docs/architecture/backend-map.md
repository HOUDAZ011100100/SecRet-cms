# Carte de l'Architecture du Backend

Ce backend est intentionnellement organisé autour de contrôleurs HTTP légers et de services métier. L'objectif n'est pas de rendre chaque fichier minuscule, mais de faire en sorte que chaque fichier soit responsable d'une couche claire.

## Ordre de Lecture

Utilisez cet ordre pour essayer de comprendre ou de déboguer une fonctionnalité :

1. `routes/api.php`
   Définit l'URL, le middleware de rôle et la méthode du contrôleur.
2. `app/Http/Requests/...`
   Valide les entrées et l'autorisation spécifique au rôle avant l'exécution du contrôleur.
3. `app/Http/Controllers/Api/...`
   Convertit la requête HTTP en un appel de service et renvoie du JSON.
4. `app/Services/...`
   Contient les règles métier, les décisions de flux de travail, les transactions et les erreurs de domaine.
5. `app/Models/...`
   Définit les collections Mongo, les champs remplissables (fillable), les casts, les accesseurs et les relations.
6. `database/migrations/2026_05_18_000000_create_mongo_indexes.php`
   Définit les index Mongo qui rendent les flux de travail sûrs et rapides.
7. `tests/Feature/...`
   Présente le comportement attendu du point de vue du consommateur de l'API.

## Standard de Commentaires

Les commentaires et les blocs de documentation (docblocks) font partie du style de documentation de la base de code et doivent rester. Ils doivent être utiles à un développeur lisant le système pour la première fois.

Les bons commentaires expliquent :

- pourquoi une règle métier existe ;
- ce qu'une transaction Mongo, une mise à jour atomique ou un index unique protège ;
- quel rôle est autorisé à effectuer une action ;
- comment l'argent, les dates, les images et les chaînes ObjectId sont représentés ;
- quelle forme de réponse le frontend attend.

Évitez les commentaires qui ne font que répéter la syntaxe PHP. Par exemple, un commentaire disant "renvoyer la réponse au format JSON" est moins utile qu'un commentaire expliquant pourquoi la réponse conserve une forme existante pour le frontend.

## Carte des Fonctionnalités

| Fonctionnalité | Routes | Contrôleur | Requêtes | Services | Modèles | Tests Principaux |
| --- | --- | --- | --- | --- | --- | --- |
| Santé | `/api/health` | `HealthController` | aucune | `HealthCheckService` | aucun | `MongoOnlyConfigurationTest`, `ApiMiddlewareTest` |
| Auth | `/register`, `/login`, `/logout`, `/user` | `AuthController` | `RegisterRequest`, `LoginRequest` | `UserWriteService` | `User`, `PersonalAccessToken` | `AuthAndUserManagementFlowTest` |
| Admin Utilisateurs | `/admin/users`, `/admin/organizers` | `UserAdminController` | `UserIndexRequest`, `StoreUserRequest`, `UpdateUserRequest` | `UserWriteService` | `User` | `AuthAndUserManagementFlowTest`, `QueryValidationTest` |
| Navigation Événements | `/events/browse`, `/events/{event}` | `EventController` | `EventIndexRequest` | `EventManagementService` | `Event`, `Feedback` | `EventManagementFlowTest`, `QueryValidationTest` |
| Gestion Événements Organisateur | `/organizer/events...` | `EventController` | `StoreEventRequest`, `UpdateEventRequest`, `UpdateEventCapacityRequest` | `EventManagementService`, `EventImageStorage` | `Event` | `EventManagementFlowTest` |
| Gestion Événements Admin | `/admin/events...` | `EventController` | `AssignEventOrganizerRequest`, requêtes d'écriture d'événement | `EventManagementService`, `EventImageStorage` | `Event`, `User` | `EventManagementFlowTest` |
| Demandes d'Événements Client | `/event-requests` | `EventRequestController` | `StoreClientEventRequest` | `EventRequestSubmissionService`, `EventRequestEligibilityService`, `EventRequestImageStorage` | `EventRequest`, `Event` | `EventRequestClientFlowTest` |
| Révision Demande Événement | `/admin/event-requests...` | `EventRequestController` | `EventRequestIndexRequest`, `ReviewEventRequestRequest` | `EventRequestReviewService` | `EventRequest`, `Event` | `EventRequestReviewFlowTest` |
| Tâches de Planification | `/organizer/events/{event}/tasks`, `/admin/events/{event}/tasks` | `EventTaskController` | `StoreEventTaskRequest`, `UpdateEventTaskRequest` | `EventTaskService` | `EventTask`, `Event` | `EventPlanningFlowTest` |
| Activités d'Événement | `/organizer/events/{event}/activities`, `/admin/events/{event}/activities` | `EventActivityController` | `StoreEventActivityRequest`, `UpdateEventActivityRequest` | `EventActivityService` | `EventActivity`, `Event` | `EventPlanningFlowTest` |
| Inscriptions Participants | `/events/{event}/register`, `/my-registrations`, `/registrations/{registration}` | `RegistrationController` | `ParticipantRegistrationIndexRequest` | `ParticipantRegistrationService`, `RegistrationService` | `Registration`, `Payment`, `Event` | `RegistrationFlowTest`, `MoneyStorageTest` |
| Gestion Inscriptions Staff | `/organizer/registrations...`, `/admin/registrations...` | `StaffRegistrationController` | `StaffRegistrationIndexRequest` | `StaffRegistrationService`, `RegistrationStatsService` | `Registration`, `Event` | `StaffRegistrationFlowTest` |
| Commentaires (Feedback) | `/events/{event}/feedback`, `/admin/feedbacks...` | `FeedbackController` | `StoreFeedbackRequest` | `FeedbackService` | `Feedback`, `Registration`, `Event` | `FeedbackFlowTest` |
| Notifications | `/notifications...` | `NotificationController` | aucune | `NotificationService` | `AppNotification` | couvert par les tests de flux |
| Stats | `/admin/stats`, `/client/stats` | `StatsController` | aucune | `AdminStatsService`, `ClientStatsService` | `Event`, `Registration`, `Payment`, `EventRequest`, `Feedback` | `StatsFlowTest`, `MoneyStorageTest` |

## Responsabilités des Couches

### Contrôleurs

Les contrôleurs doivent rester courts. Ils sont responsables de :

- recevoir des requêtes déjà authentifiées ;
- extraire l'acteur authentifié ;
- passer les données validées à un service ;
- préserver la forme de réponse de l'API attendue par le frontend.

Les contrôleurs ne doivent pas contenir de flux de travail métier en plusieurs étapes, de logique de capacité, de règles d'approbation ou de détails sur les transactions Mongo.

### Form Requests

Les Form Requests sont la première frontière explicite pour la sécurité des entrées. Elles sont responsables de :

- valider les chaînes, les dates, les nombres, les valeurs ObjectId et les énumérations ;
- appliquer une autorisation de rôle simple lorsque le rôle suffit à décider de l'accès ;
- maintenir les filtres de chaîne de requête (query-string) hors des contrôleurs.

### Services

Les services constituent la couche métier du backend. Ils sont responsables de :

- règles de cycle de vie des événements ;
- règles d'inscription et de paiement ;
- règles de modération des commentaires ;
- éligibilité des demandes des clients ;
- décisions de révision des administrateurs ;
- diffusion des notifications ;
- transactions Mongo et mises à jour atomiques.

### Modèles (Models)

Les modèles décrivent les documents Mongo et le comportement de sérialisation de l'API. Ils sont responsables de :

- noms de collections ;
- champs remplissables (fillable) ;
- casts pour les dates BSON et les tableaux ;
- champs publics calculés tels que `image_url`, `ticket_price` et `amount` ;
- relations entre les champs de chaîne ObjectId.

## Règles de Données MongoDB Exclusivement

- `DB_CONNECTION` doit rester `mongodb`.
- Les identifiants de relation publics sont des chaînes Mongo ObjectId.
- L'argent est stocké en centimes entiers et exposé via des champs d'API compatibles avec les décimales.
- Les dates sont stockées sous forme de dates BSON Mongo et sérialisées en ISO-8601 via Laravel.
- La prévention des doublons est assurée par des index uniques Mongo, avec des vérifications au niveau du service pour des erreurs conviviales.

## Règles de Sécurité les Plus Importantes

| Règle | Lieu d'Application |
| --- | --- |
| Pas d'email utilisateur en double | Index `users_email_unique` et validation utilisateur |
| Pas d'inscription en double pour le même événement et participant | Index `registrations_event_user_unique` et `RegistrationService` |
| Pas de commentaire en double pour le même événement et participant | Index `feedbacks_event_user_unique` et `FeedbackService` |
| Pas de surréservation d'événement | Incrémentation atomique conditionnelle dans `RegistrationService` |
| Pas de réduction de capacité en dessous des inscriptions actuelles | `EventManagementService` |
| Pas de publication directe par l'organisateur | `EventManagementService` |
| Pas de révision répétée d'une demande d'événement | Mise à jour conditionnelle du statut dans `EventRequestReviewService` |
| Pas de suppression d'inscription payée | `RegistrationService` et `StaffRegistrationService` |
| Pas d'accès à l'API non authentifié | Middleware `auth:sanctum` |
| Pas d'accès à une route avec le mauvais rôle | Middleware `role` et Form Requests |
