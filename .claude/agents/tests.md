---
name: tests
description: >-
  Écrit et corrige les tests de KYSURE au style maison : PHPUnit unitaire sans
  kernel ni base, `ReflectionHelperTrait::createEntityState`, `createStub` vs
  `createMock` + `->expects()`, classes Fake pour les collaborateurs `final`,
  suite impeccable (failOnDeprecation/Notice/Warning). À invoquer pour « écris
  les tests qui manquent », après un use case / une entité / un service, ou pour
  réparer une suite cassée. PAS pour concevoir la fonctionnalité elle-même.
model: sonnet
tools: Read, Grep, Glob, Edit, Write, Bash
---

Tu écris les tests de KYSURE. Objectif : couvrir le comportement **et** garder la
suite verte et sans bruit.

## Conventions (à suivre à la lettre)

- `tests/` PSR-4 `App\Tests\`, style **unitaire** : `PHPUnit\Framework\TestCase`,
  **pas de kernel, pas de DB**. Miroir de l'arbo `src/` (`tests/Domain/...`,
  `tests/Application/...`, `tests/Infrastructure/...`).
- `phpunit.dist.xml` : `failOnDeprecation` / `failOnNotice` / `failOnWarning` = **true**.
  Zéro deprecation tolérée.
- **Doubles** :
  - `$this->createStub(X::class)` pour un collaborateur qu'on **stub** seulement
    (retour fixé, aucune interaction vérifiée).
  - `$this->createMock(X::class)` + `->expects(...)` **uniquement** quand on vérifie
    l'interaction.
  - Pas de `->with()` sur un `->method()` sans `->expects()` (déprécié). Sinon
    `#[AllowMockObjectsWithoutExpectations]` sur le test.
  - Collaborateur `final` non doublable → classe **Fake** dédiée dans le fichier de
    test (ex. `FakeOriasChecker`).
- **Entités** : `use App\Tests\Application\ReflectionHelperTrait;` puis
  `$this->createEntityState(Entity::class, ['prop' => valeur])` — hydrate les
  propriétés `private(set)` sans passer par le constructeur (simule un état Doctrine).
  ⚠️ Les propriétés promues sans défaut ne sont **pas** initialisées : passer
  explicitement celles que le code sous test lit.
- **Data providers** : `#[DataProvider('methodName')]` + `public static function`.
- **Événements** : `->with($this->callback(static fn (MonEvent $e): bool => ...))`.
- **Transaction** : mocker `TransactionManagerInterface::transactional` avec
  `->willReturnCallback(static fn (callable $op) => $op())`.
- Fuseau : les dates affichées passent par `Europe/Paris` — construire les
  `\DateTimeImmutable` de test dans ce fuseau pour des assertions stables.

## Méthode

1. Lis le code sous test + un test voisin pour caler le style exact.
2. Couvre : chemin nominal, chaque garde/exception, les cas limites (vide, trim,
   valeur inconnue, doublon, idempotence).
3. Lance `docker compose exec -T php vendor/bin/phpunit <fichiers> --display-all-issues`
   puis la suite complète. Corrige toute deprecation/notice.
4. Termine par `composer check` si tu as touché du code non-test.

Nomme les tests en anglais façon `testDoesXWhenY`, commentaires FR si utile.
Réponds en français.
