# BFMW — guide d'utilisation à partir de l'exemple `applicationTest`

Ce README documente **l'utilisation du framework BFMW** à partir de l'application d'exemple contenue dans `applicationTest`.

> Note dépôt : le fichier composer est volontairement nommé `_composer.json` pour que Packagist ignore cette branche d'exemple.

## 1) Démarrer l'exemple

1. Installer les dépendances :
   ```bash
   composer install
   ```
2. Préparer le fichier d'environnement `.env` attendu par l'application.
3. Pointer le serveur web vers `applicationTest/index.php`.

## 2) Cycle de vie BFMW dans l'exemple

### Point d'entrée

Le fichier `applicationTest/index.php` :
- charge l'autoload Composer ;
- appelle `Application::init()` ;
- instancie `ApplicationDeTest` avec :
  - un authenticator (`ApplicationTestAuthenticator`),
  - un connecteur MySQL,
  - un interceptor (`ApplicationTestInterceptor`).

### Classe d'application

`ApplicationDeTest` illustre la structure type d'une application BFMW :
- `run()` construit l'interface globale :
  1. header global,
  2. menu,
  3. générateur de page active (`$_SESSION[$this->sessionPage]`),
  4. footer global ;
- `getFavIcon()` définit le favicon.

### Authentification

`ApplicationTestAuthenticator` se base sur `UcaAuthenticator` (CAS), puis enrichit l'utilisateur authentifié avec des données de démo (`name`, `status`).

### Interception

`ApplicationTestInterceptor` montre les deux points d'extension majeurs :

- `frontInterceptor()` : gestion des requêtes POST classiques (page `Forms`) avant routage normal.
  - réception d'un formulaire `nom`,
  - traitement de paramètres encodés,
  - retour de messages utilisateur.
- `bindingInterceptor()` : gestion des callbacks asynchrones (`data-bfmw-binding`, `data-bfmw-manual-binding`) avec réponses JSON via `sendBindingResponse()`.

## 3) Ce qu'il se passe sur chaque page

La navigation est définie par `applicationTest/templates/menu.html` et pilotée par le générateur `Menu` qui applique la classe `active` selon la page courante.

### `?p=Accueil` — Démo Master/Detail

- Générateur : `applicationTest/generators/Accueil.php`.
- Template : `applicationTest/templates/accueil.html`.
- Comportement :
  - charge une liste d'étudiants (`Etudiants::getEtudiantsDuDepartement(1,10)`) dans le bloc répété `un_etudiant` ;
  - affiche une vue Master/Detail (liste à gauche, détail à droite) ;
  - inclut un token CSRF par défaut ;
  - simule une latence de rendu (`sleep(2)`) pour tester les états de chargement.

### `?p=Md` — Démo Master + filtres / Detail

- Générateur : `applicationTest/generators/Md.php`.
- Template : `applicationTest/templates/md.html`.
- Comportement :
  - même principe Master/Detail,
  - ajoute une zone de filtres (deux `<select>`),
  - peuple la zone master avec la même source d'étudiants.

### `?p=Limited` — Layout limité

- Générateur : `applicationTest/generators/Limited.php`.
- Template : `applicationTest/templates/limited.html`.
- Comportement :
  - montre le conteneur `limited` pour les pages à largeur contrainte,
  - sert d'exemple de scroll long et de mise en page standard BFMW.

### `?p=Maximal` — Layout maximal

- Générateur : `applicationTest/generators/Maximal.php`.
- Template : `applicationTest/templates/maximal.html`.
- Comportement :
  - montre le conteneur `maximal` (zone de contenu plus large),
  - permet de comparer rapidement les comportements CSS entre `limited` et `maximal`.

### `?p=Modal` — Messages et modales

- Générateur : `applicationTest/generators/Modal.php`.
- Template : `applicationTest/templates/modal.html`.
- JS associé : `applicationTest/js/specific_modal.js`.
- Comportement :
  - déclenche un message automatique au chargement (`data-bfmw-show-message`),
  - propose des boutons pour afficher des messages de types `error`, `warning`, `info`, `success`,
  - montre aussi l'apparence d'actions désactivées.

### `?p=Forms` — Formulaires, CSRF, paramètres encodés et binding

- Générateur : `applicationTest/generators/Forms.php`.
- Template : `applicationTest/templates/forms.html`.
- JS associé : `applicationTest/js/specific_forms.js`.
- Interception : `ApplicationTestInterceptor`.
- Comportement :
  - injecte plusieurs tokens CSRF (`form`, `param`, `delete`),
  - injecte des paramètres BFMW encodés (helpers `paramGenerator`),
  - démontre une soumission POST classique,
  - démontre une action DELETE simulée (`data-bfmw-method="DELETE"`),
  - démontre le binding asynchrone sur `input`, `checkbox` (manuel) et `select`,
  - affiche un retour visuel succès/échec de validation du binding.

### `?p=TreeView` — Arbre hiérarchique

- Générateur : `applicationTest/generators/TreeView.php`.
- Template : `applicationTest/templates/treeview.html`.
- Comportement :
  - initialise un composant arborescent via `data-bfmw-treeview`,
  - affiche une structure hiérarchique multi-niveaux,
  - illustre la compatibilité avec la mécanique auto-hide existante (`bfmw_auto_hidder`).

## 4) Ce que l'exemple montre du framework

En pratique, `applicationTest` sert de référence pour :

- **Architecture BFMW** : entrée unique, classe d'application concrète, générateurs spécialisés.
- **Templating** : affectation simple (`affectToHTML`) et blocs répétés (`affectToBlocAndRepeat`).
- **Sécurité web** : génération/validation des tokens CSRF.
- **Transport de contexte** : paramètres encodés côté client puis décodés côté serveur.
- **UX asynchrone** : binding côté front + interception serveur + retour JSON standardisé.
- **Composants UI** : menu actif, messages modaux, treeview, layouts limités/maximaux, master/detail.

## 5) Arborescence utile

```text
applicationTest/
  index.php
  ApplicationDeTest.php
  core/
    ApplicationTestAuthenticator.php
    ApplicationTestInterceptor.php
  generators/
    Accueil.php
    Forms.php
    Limited.php
    Maximal.php
    Md.php
    Menu.php
    Modal.php
    TreeView.php
  templates/
    accueil.html
    forms.html
    limited.html
    maximal.html
    md.html
    menu.html
    modal.html
    treeview.html
  css/
  js/
```
