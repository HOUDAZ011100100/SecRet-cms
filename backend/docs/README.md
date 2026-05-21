# Documentation du Backend VELORA

Ce répertoire contient trois couches de documentation :

- Pages de référence de classe phpDocumentor générées pour les composants internes du backend.
- `api/` contient la documentation de l'API rédigée pour les routes, les rôles, les corps de requête et les contrats destinés au frontend.
- `architecture/`, `merise/`, et `uml/` contiennent des explications sur le backend, l'analyse Merise et les diagrammes de flux de travail.

Seule la documentation rédigée dans ce dossier est versionnée :

- `README.md`
- `api/README.md`
- `api/index.html`
- `api/openapi.yaml`
- `architecture/backend-map.md`
- `merise/README.md`
- `operations/hardening.md`
- `uml/workflows.md`

La sortie générée de la documentation de classe et le cache `backend/.phpdoc/` sont des artefacts de construction locale et ne doivent pas être commités.

## Documentation de l'API

- Guide des routes lisible par l'homme : `api/README.md`
- Contrat OpenAPI 3.1 : `api/openapi.yaml`
- Visionneuse HTML ReDoc : `api/index.html` charge directement `api/openapi.yaml`

## Architecture et Diagrammes

- Carte de lecture du backend et standard de commentaires : `architecture/backend-map.md`
- Plan de durcissement et checklist production : `operations/hardening.md`
- Documentation Merise avec MCD, MLD et MCT : `merise/README.md`
- Diagrammes de flux de travail de style UML pour les flux de l'application : `uml/workflows.md`

## Standard de Commentaires

Les commentaires de code et les blocs de documentation (docblocks) doivent rester. Ils font partie de la couche de lisibilité du backend. Améliorez-les lors de la modification d'un fichier, en particulier autour des règles métier, des transactions Mongo, des mises à jour atomiques, des vérifications de rôle, de la représentation de l'argent/des dates et de la compatibilité des réponses frontend.

## Régénération de la Documentation de Classe

Depuis la racine du dépôt :

```bash
docker run --rm -v "$PWD/backend:/data" phpdoc/phpdoc:3 \
  -d /data/app \
  -t /data/docs \
  --title "Documentation de l'API VELORA" \
  --cache-folder /data/.phpdoc/cache
```

Après régénération, exécutez les vérifications du backend avant de commiter :

```bash
docker compose run --rm backend composer test
docker compose run --rm backend ./vendor/bin/pint --test
docker compose run --rm backend ./vendor/bin/phpstan analyse --memory-limit=512M
```
