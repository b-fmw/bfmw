# BFMW usage example (`applicationTest`)

This document describes **only** the `applicationTest` example available in this repository.

## Example purpose

This example shows how to assemble a complete BFMW application with:
- an entry point (`applicationTest/index.php`);
- a concrete application class (`ApplicationDeTest`);
- a custom authenticator (`ApplicationTestAuthenticator`);
- an interceptor for forms and bindings (`ApplicationTestInterceptor`);
- multiple page generators (`applicationTest/generators/*`);
- related templates, CSS, and JS assets.

## Running the example

1. Install dependencies:
   ```bash
   composer install
   ```
2. Configure the expected environment file (`.env`).
3. Point your web server to `applicationTest/index.php`.

## Execution flow

1. `applicationTest/index.php` initializes BFMW with `Application::init()`.
2. The app instantiates `ApplicationDeTest` with an authenticator, DB connector, and interceptor.
3. `ApplicationDeTest::run()` builds the global header, menu, active page, and footer.
4. `ApplicationTestInterceptor` can short-circuit routing to process POST and asynchronous binding requests.

## Documentation coverage for example classes and methods

### 1) `applicationTest\ApplicationDeTest`

- **Class**: concrete demo application.
- **Documented methods**:
  - `run(): void`: builds shared generators and the requested page.
  - `getFavIcon(): string`: returns the favicon path used by the global header.

### 2) `applicationTest\core\ApplicationTestAuthenticator`

- **Class**: test authenticator based on `UcaAuthenticator`.
- **Documented methods**:
  - `authenticate(): array|false`: delegates to CAS authentication and enriches returned test user data.

### 3) `applicationTest\core\ApplicationTestInterceptor`

- **Class**: example interceptor for request pre-processing.
- **Documented methods**:
  - `frontInterceptor(): bool`: handles `Forms` page POST requests (messages and encoded parameter reading).
  - `bindingInterceptor(): bool`: handles asynchronous binding callbacks and returns JSON responses.

### 4) Generators (`applicationTest\generators\*`)

Each generator class has a class DocBlock and a constructor DocBlock.

- `Accueil::__construct(ApplicationDeTest $application)`: home page + repeatable student block example.
- `Forms::__construct(ApplicationDeTest $application)`: CSRF, encoded parameters, and binding demo.
- `Limited::__construct(ApplicationDeTest $application)`: limited layout rendering example.
- `Maximal::__construct(ApplicationDeTest $application)`: maximal layout rendering example.
- `Md::__construct(ApplicationDeTest $application)`: Master/Detail example.
- `Menu::__construct(ApplicationDeTest $application)`: navigation menu and active state.
- `Modal::__construct(ApplicationDeTest $application)`: modal dialog demo.
- `TreeView::__construct(ApplicationDeTest $application)`: tree view demo.

## Documentation verification (result)

Verification result: all PHP classes in the example and all methods declared in `applicationTest`, `applicationTest/core`, and `applicationTest/generators` are documented with DocBlocks.

## Example structure

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
  css/
  js/
```
