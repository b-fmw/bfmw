# BFMW — usage guide based on the `applicationTest` example

This README documents **how to use the BFMW framework** using the sample application located in `applicationTest`.

> Repository note: the composer file is intentionally named `_composer.json` so Packagist ignores this example branch.

## 1) Run the example

1. Install dependencies:
   ```bash
   composer install
   ```
2. Prepare the `.env` file expected by the application.
3. Point your web server to `applicationTest/index.php`.

## 2) BFMW lifecycle in this example

### Entry point

The `applicationTest/index.php` file:
- loads Composer autoload;
- calls `Application::init()`;
- instantiates `ApplicationDeTest` with:
  - an authenticator (`ApplicationTestAuthenticator`),
  - a MySQL connector,
  - an interceptor (`ApplicationTestInterceptor`).

### Application class

`ApplicationDeTest` demonstrates a typical BFMW application structure:
- `run()` builds the global UI:
  1. global header,
  2. menu,
  3. active page generator (`$_SESSION[$this->sessionPage]`),
  4. global footer;
- `getFavIcon()` defines the favicon.

### Authentication

`ApplicationTestAuthenticator` is based on `UcaAuthenticator` (CAS), then enriches the authenticated user with demo data (`name`, `status`).

### Interception

`ApplicationTestInterceptor` demonstrates the two main extension points:

- `frontInterceptor()`: handles regular POST requests (on the `Forms` page) before normal routing.
  - receives a `nom` form field,
  - decodes encoded parameters,
  - returns user messages.
- `bindingInterceptor()`: handles asynchronous callbacks (`data-bfmw-binding`, `data-bfmw-manual-binding`) and returns JSON responses through `sendBindingResponse()`.

## 3) What happens on each page

Navigation is defined in `applicationTest/templates/menu.html` and controlled by the `Menu` generator, which applies the `active` class to the current page.

### `?p=Accueil` — Master/Detail demo

- Generator: `applicationTest/generators/Accueil.php`.
- Template: `applicationTest/templates/accueil.html`.
- Behavior:
  - loads a student list (`Etudiants::getEtudiantsDuDepartement(1,10)`) into the repeated `un_etudiant` block;
  - renders a Master/Detail layout (list on the left, detail on the right);
  - includes a default CSRF token;
  - simulates rendering latency (`sleep(2)`) to test loading states.

### `?p=Md` — Master + filters / Detail demo

- Generator: `applicationTest/generators/Md.php`.
- Template: `applicationTest/templates/md.html`.
- Behavior:
  - same Master/Detail principle,
  - adds a filter area (two `<select>` fields),
  - populates the master area with the same student source.

### `?p=Limited` — Limited layout

- Generator: `applicationTest/generators/Limited.php`.
- Template: `applicationTest/templates/limited.html`.
- Behavior:
  - shows the `limited` container for constrained-width pages,
  - serves as a long-scroll and standard BFMW layout example.

### `?p=Maximal` — Maximal layout

- Generator: `applicationTest/generators/Maximal.php`.
- Template: `applicationTest/templates/maximal.html`.
- Behavior:
  - shows the `maximal` container (wider content area),
  - allows quick CSS behavior comparison between `limited` and `maximal`.

### `?p=Modal` — Messages and modal-like interactions

- Generator: `applicationTest/generators/Modal.php`.
- Template: `applicationTest/templates/modal.html`.
- Associated JS: `applicationTest/js/specific_modal.js`.
- Behavior:
  - triggers an automatic message on load (`data-bfmw-show-message`),
  - provides buttons to display `error`, `warning`, `info`, and `success` messages,
  - also shows the visual state of disabled actions.

### `?p=Forms` — Forms, CSRF, encoded parameters, and binding

- Generator: `applicationTest/generators/Forms.php`.
- Template: `applicationTest/templates/forms.html`.
- Associated JS: `applicationTest/js/specific_forms.js`.
- Interception: `ApplicationTestInterceptor`.
- Behavior:
  - injects multiple CSRF tokens (`form`, `param`, `delete`),
  - injects encoded BFMW parameters (via `paramGenerator` helpers),
  - demonstrates a classic POST submission,
  - demonstrates a simulated DELETE action (`data-bfmw-method="DELETE"`),
  - demonstrates asynchronous binding on `input`, `checkbox` (manual), and `select`,
  - displays visual success/failure feedback for binding validation.

### `?p=TreeView` — Hierarchical tree

- Generator: `applicationTest/generators/TreeView.php`.
- Template: `applicationTest/templates/treeview.html`.
- Behavior:
  - initializes a tree component using `data-bfmw-treeview`,
  - displays a multi-level hierarchical structure,
  - illustrates compatibility with the existing auto-hide behavior (`bfmw_auto_hidder`).

## 4) What this example demonstrates about the framework

In practice, `applicationTest` is a reference for:

- **BFMW architecture**: single entry point, concrete application class, specialized generators.
- **Templating**: direct assignment (`affectToHTML`) and repeated blocks (`affectToBlocAndRepeat`).
- **Web security**: CSRF token generation/validation.
- **Context transport**: client-side encoded parameters decoded on the server side.
- **Asynchronous UX**: front-end binding + server interception + standardized JSON responses.
- **UI components**: active menu, modal messages, treeview, limited/maximal layouts, master/detail.

## 5) Useful project tree

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
