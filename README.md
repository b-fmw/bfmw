# BFMW

BFMW is a **lightweight PHP framework** focused on server-rendered pages, secure form handling, and reusable UI building blocks.

It is designed for teams that want:
- a clear application bootstrap flow,
- strict request sanitization conventions,
- built-in CSRF and parameter tokenization,
- a small templating engine with block-based rendering,
- and optional ready-to-use front-end helpers (modal, updater, binding, treeview, responsive menu).

> This README documents the current framework architecture and usage. A dedicated examples section will be added later.

---

## What BFMW is for

BFMW is a good fit when you build:
- internal tools,
- administration portals,
- business web apps with classic page navigation,
- applications where form security and predictable request lifecycle matter.

It provides a structured base to:
1. initialize environment + session,
2. sanitize incoming data,
3. validate CSRF on POST,
4. decode short-lived tokenized parameters,
5. authenticate users,
6. route to page generators,
7. render shared header/footer and templates.

---

## Core concepts

### 1) `Application` as the orchestrator
You create a concrete class that extends `bfmw\Application`.

The base class handles:
- autoload registration,
- environment loading,
- headers + sanitization,
- CSRF check for POST requests,
- parameter-token cleanup policy,
- DB connection lifecycle start,
- authentication,
- routing with admin/non-admin generator resolution.

You only need to implement:
- `run(): void` (your page execution logic),
- `getFavIcon(): string` (favicon URL for global header rendering).

### 2) Generators = page controllers
BFMW uses `PageGenerator`-based classes as controller/rendering units.

Built-in generators include:
- `OverallHeader` (loads common CSS/JS and optional UI modules),
- `OverallFooter` (shared footer + DB disconnect),
- `CsrfGenerator` (inline CSRF fields/attributes),
- `ParametersGenerator` (inline encoded parameter token fields/attributes).

### 3) Tokenized request payloads
Instead of exposing sensitive operational values directly in HTML, BFMW can store payloads in session and send only a generated token in forms/attributes:
- `Csrf` manages request forgery tokens by logical context,
- `ParametersEncoder` stores arbitrary parameter arrays behind TTL/one-time tokens.

### 4) Templating layer
BFMW ships with:
- `TemplateEngine`: low-level parser/compiler,
- `Templating`: higher-level wrapper for assigning page vars and repeating blocks.

This supports:
- global variable assignment,
- block iteration,
- conditional rendering primitives,
- reusable global templates.

### 5) Data access abstraction
`DBConnector` defines the database contract.

`MySQLDBConnector` provides a mysqli implementation including:
- read helpers,
- write helpers,
- transaction helpers,
- convenience `createData(...)` insertion based on BFMW-secured keys.

### 6) Optional request interception
`Interceptor` allows pre-routing behavior:
- `frontInterceptor()`
- `bindingInterceptor()`

If an interceptor returns `true`, default routing is skipped.

---

## Built-in security model

BFMW includes multiple layers by default:

- **Security headers** (`Framework::sendHeaders`) such as CSP, frame protection, referrer policy, etc.
- **Global input sanitization** (`Framework::sanitize`) for `$_GET`, `$_POST`, `$_COOKIE`, `$_REQUEST`.
- **Dual-value secure mapping** (`Helpers::manualBfmwSecure`) creating:
  - `bfmw_orig_*` values,
  - `bfmw_num_*` numeric-normalized values.
- **CSRF validation on POST**: invalid token clears `$_POST` before business logic.
- **Token expiry + one-time semantics** for CSRF and encoded parameters.
- **Session cookie hardening** in `Authenticator` (`HttpOnly`, `SameSite=Strict`, conditional `Secure`).

---

## Front-end assets included

The package contains default CSS and JavaScript modules under `src/css` and `src/js`.

Notable built-in JS features:
- load queues,
- responsive menu toggling,
- master/detail responsive behavior,
- loading overlay during submits/navigation,
- modal message display,
- binding/update helpers,
- request helper functions,
- treeview interactions.

`OverallHeader` auto-injects BFMW asset bundles and optional local page assets (`css/style.css`, `js/main.js`, and per-page `specific_<page>.css/js` when present).

---

## Installation

### Requirements
- PHP `>= 8.5`
- Extensions:
  - `ext-mysqli`
  - `ext-intl`
  - `ext-simplexml`

### Composer
```bash
composer require b_fmw/bfmw
```

### Autoload namespace
```json
"autoload": {
  "psr-4": {
    "bfmw\\": "src/"
  }
}
```

---

## Project integration checklist

When integrating BFMW into an app:

1. **Initialize once** with `Application::init()` before creating your application instance.
2. **Provide an environment file** and call the parent constructor with its path.
3. **Implement an `Authenticator`** subclass:
   - `authenticate()`
   - `isAdmin()`
   - `isRegistered()`
4. **Provide a `DBConnector`** (typically `MySQLDBConnector`).
5. **Implement your concrete `Application::run()`** to instantiate and execute your page generator(s).
6. **Use BFMW templates/generators** for consistent header/footer and secure inline tokens.
7. **Read request data using BFMW conventions** (`bfmw_orig_*` / `bfmw_num_*`).

---

## Runtime flow (high-level)

A typical request follows this order:

1. Session starts (if not active).
2. Environment variables are loaded from file.
3. Security headers are sent.
4. Superglobals are sanitized and transformed.
5. If POST, CSRF is validated.
6. Parameter tokens are cleaned when no encoded payload is posted.
7. Timezone is configured.
8. Database connector is initialized and connected.
9. Authentication is executed and persisted in session.
10. Interceptors run (binding/front).
11. Router resolves page generator (including admin fallback logic).
12. Your `run()` executes page rendering and logic.

---

## Directory overview

```text
src/
  core/            # application lifecycle, security, helpers, DB abstraction
  templating/      # template engine + wrapper
  generators/      # reusable generators (header/footer/csrf/params)
  repository/      # abstract repository base
  css/             # framework styles
  js/              # framework JS modules
  images/          # framework assets
  global_templates/# shared template fragments
```

---

## Licensing

This project is distributed under **CC BY-NC-ND 4.0**.

Please read the license carefully before using it in production or redistributing any part of the framework.

---

## Status of examples

A complete "Examples" section (quick start app, form flow, generator wiring, binding/update endpoint pattern) is planned and will be added once development examples are finalized.
