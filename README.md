# BFMW

BFMW is a **lightweight PHP framework** focused on server-rendered pages, secure form handling, and reusable UI building blocks.

It is designed for teams that want:
- a clear application bootstrap flow,
- strict request sanitization conventions,
- built-in CSRF and parameter tokenization,
- a small templating engine with block-based rendering,
- and optional ready-to-use front-end helpers (modal, updater, binding, treeview, responsive menu).

> This README documents the current framework architecture and usage. A dedicated sample branch is available.

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

## Class diagram with the classes of the sample application

[![Class diagram](https://img.plantuml.biz/plantuml/svg/xLbjRziu4lxkNo6ungs3rDa21nnkYxrsbLXfWzf9rdRkK1mE0YtH3X5AL4agBzlslzyCgHTIahDPxCSxVqWapd0u7CxBCzGlIXEfouoFRbbAWuJ9XDCHuLmyPEoHvVkHkcDvGIJ9WeGJfKAj9TkMcbua8b-ptsZmfijoIfAxlHHbdfu9BcJmQWU_xKyzDY5JAYkMlx_1NsynASN3inpd8jSLmvR3kaFqIvBHO4DkHKP68qvtEj0Ya6n_guEKIPfe9lBHY6oLbYJHTbuG5WLd2K5Qy7KK13__l6CvuU_U1L_1P9ebtuy_H9VHAjnShTRn-TLY7gtU1Y02RJR87iwN5vSlufn7v-7DOXCt2pZinueg1KfTaprrXf7I20XOplJqA7WRt0kM-YHIQZ0NIQTscxK8bzdP-dH2y_junpXjE5y74v1nItEDF63ujq7elWFTKT6TbcxFH9w3lSsa-Mdp1ZIMQoeJMi0RAdNHlWR_27Bwu8vCZq3B6Nsa2RZXEPDANqkHm9hJesOJC_0_JPs1uptLdfw6snj1hepSqGyqfn87ffDz_UXjN-vHHo8bUGBj_lqV48HxRUQ1eA-rEtq7uyioTpJnIA3LERd7M4AEwZY_ZqQk-yrcxuBnSH37UowsX7-alA1InV4u2AhdOVQKQE8mEmRlyy-27SGCVH3oRXoSruzzFl_eNuqx5dJvJqxUkQVMevqfkUiDHldz0hoJzjbz2_Ufg3Cifg8y4JY_5z2Uvbyx1-7iotYDETnlEy8LxN0o5QOPCyUr5OBtg2kwPmeKeQd3ShoLbDojRLouNgd8FGrLJPZWaTMkLNE1DzWTeC7ST36OF89Pi8xMwukho_XJz3bmKiIiAEMUHey5gtIhTT3YZiBQB6sumQj_IEcEb5p_00wlDLzJSD4KtHuY5SX__TkRDq0HETsm3ACSDmhZMfRqo4rNzuGps1KzboAx5aetHcct9J9CsFMfpExeawirvJI3aza8omT9xtrBL415XtOde0h_9Bnqq-FCd6Ht5FLhLvzEO6Ktphih1ytI9iVnEfCrDXkleqLqjedcOpHQhOR7OQVH53bQdJoMyL6LhZxHfwbdInYBEZeXmtkFOrqfrfkwgHNgKSuRHVmaP9BoZYJKpHtca8vJfX8xqicnS_3WwKJIBsrfgVpPOVWNqxUukmc0ZPrhyaFpKh6YNqeAxkEp80qBmJ2r3J_aMO8tivSmLZmhgakPXxkTER05owdgJGCB3SvQ4AKlqaFi1dDj9CaLCU33DM4YienvHfM2yor06uyBqeccrjQQR3cDSv8vnTPB9AydEqPvgc8jub9H6yEl9-fMFCIWitngmzUuPALe4w1DJkuadrakP0RX_LlbEvKBJ-xnpVCn0pWc1T6txc6BUoebeixUydxUQygHTJr5y_GZHNsKdqa1z80kKsT-EnDhX5CpQj5LUJoKIvVXXukpU1vkelXp5Aw2-WIVPrrUNMu-lf1t7duUu8nMgwjLl8Gy7dw8rcqIjmWbBma_tMKFa9DBo01_cHGM_dGBXustopAZaYLJF1RW05HFqwFwmPGc41McgPMMWhW1tt4ywxvAM5PGZPcMi3uKwEaaim-Rfw9XmcJ8j7JVAwv6JAinKv6Kj9HkyToXds7YeAP2uZHymSfpKQSe40h-1I-vbqmZ-0Lfi8HVy0I_iUUN46m5mCHmNmc-sfqowJXf6xAGQnNrDB0SttgA73dAPLbfejEJ4ENfrMvEdlwS91o5s6UP1x4LhKjfKnOGFvUSUzcqaeer6Uh85b8RX6TIFRcx7roVFXgqKql0_-3PMM7LCO_Mftz7mBo21NzS0FYfQTomZW3E_nMJc460c6JWdzg8JvGMpKvEJawSh507lGNA2Q2giS5MBRhqEOp8XZXsi6OxeyudEkEOcnf29xESNooYUFFvsWHrVHupc0brqUn8I0_y5EKMKjOG2StGMsqwyKMvhj4HvTNG6GV86k9KDNKSPROj6KytP3-TO0juwPQO3eOm2dJWWpM9sygP_BsJq9DxxPm3arcUmknXwYlC_AUMOqLL8N858Q3Af4f-oDRcbYe3ERZAeJ7D3n8xOAnRZPPFwryNNJpq2i4G9BCdzOKpj5oJwqs1FIF9BQtjwZIlnd6RkWbWeJQ-FTy1XFL_tFM_WBjwFdPoyksTry1tkrklExPnvWrDDpGhEBWaEXhDzs3L8rnf89TLMSyoZGFTJ3nfPTJ1q6kxkn1n0FqSQ4zVqA7QtDwOzyyrfX0IHdjmBYFUzPVtJG10e90yvBpTwBIN4TOCp4BFmdLtop0KN_hmRMQAzvhAUvOWR3QzSPlC7ZE-jjbW81fdeF9P2UWerpUIJoTReYWy39NwjeAO55wj1ynLnJmZgjvyQIJqa_fCsi02dzE4vMh3aZlQjEysGNct9fDRQE-TK2Y4rBNFmnbfgFfkoZcaFLuKCeMnjLulAWlXBKylLgnPu1GA25BCsyrb7U9vvywa4nTriRh8EJWu85QHUXXgGDT1RphiVmDLy0fw7SBvkH3WGrOFRmWgjtEZzTRjLKoS-lExtKoVsW_LYiTsJjPJdM2z3iy-GQyHhwzkLcTHV1sQ3gPrXW7MSBsEDl5fk8xYczN2vJxsrdfcQs321t38fKXDR-8R5oat1SQ7x8InOv1gEmSCub3hcz81IXT53V0yb_d-e7lv5UBFUDQG2iDteOSutMs1G2U0PhDltIlNQkDXLNXMj12A6IXfzjqVdxeLR9BKZ-Ru1gIOijDmuTsoL_3gA6_hKHlbrJwWjSAQPVH1d_tnHm3-RGnkt9buKLZDYYN4ZUliBCMajhDNnOXwExYx5OItfXrUzwmHDLztRZsyLJsiMLEKkG6S0HeFjGRaY1T-qxuifpuI0vOsukDckS16NyO6d0nS28Y2vgTS950zuA-FNWO47c9z7JoxYZizp5CuP81DLaxNH5uue9TaJWVhN0qtnI53E6aZtJBLts4gkXPp4Mfeieq0BbGfQKkzeqzL2RHA3HLFxpxL1ZCgmY60h84Vh8GekHNzieIltzeWTPok-j1I7_EuuxOkg_a-qcn-rFgma-GsK9pzZnglThkVu5Vv_t7mt4SD_njJNvP903S9yR_l36lXjdXT7VhTNj2WVgGFTNmrnu2Ixb96nGDVcBgymtdnToOCN1WwGtEASxmDTp_rlUZxNYN7x6PWnUuPT1Py_iFLST3_N8LZ0n-fGAdUKbOdpmDwF6Ye1W4tLyQwJ1d9jUhv0tx3g8dpSextLPhQ39U2vMFp5SfWHWAfdpRlHu63-5hnLiDUCN4u3Xmak1rzHEssyBEV0-i6vJZ825hCVW91jewCNhGSPdFTSv1fUDJwX4FhTeHjFIpWZmDumoGfAUCTBwb6fmUHc5tGbkPAWlaSMvknkOiNCbCTuMRiHQBjx0Cdic3GWDEq8xmQVP7uMiAWvikkvENBX2vxyapBWKOV04cMW1DQdE8Czezuz0lDqpBZ_mK0)](https://img.plantuml.biz/plantuml/svg/xLbjRziu4lxkNo6ungs3rDa21nnkYxrsbLXfWzf9rdRkK1mE0YtH3X5AL4agBzlslzyCgHTIahDPxCSxVqWapd0u7CxBCzGlIXEfouoFRbbAWuJ9XDCHuLmyPEoHvVkHkcDvGIJ9WeGJfKAj9TkMcbua8b-ptsZmfijoIfAxlHHbdfu9BcJmQWU_xKyzDY5JAYkMlx_1NsynASN3inpd8jSLmvR3kaFqIvBHO4DkHKP68qvtEj0Ya6n_guEKIPfe9lBHY6oLbYJHTbuG5WLd2K5Qy7KK13__l6CvuU_U1L_1P9ebtuy_H9VHAjnShTRn-TLY7gtU1Y02RJR87iwN5vSlufn7v-7DOXCt2pZinueg1KfTaprrXf7I20XOplJqA7WRt0kM-YHIQZ0NIQTscxK8bzdP-dH2y_junpXjE5y74v1nItEDF63ujq7elWFTKT6TbcxFH9w3lSsa-Mdp1ZIMQoeJMi0RAdNHlWR_27Bwu8vCZq3B6Nsa2RZXEPDANqkHm9hJesOJC_0_JPs1uptLdfw6snj1hepSqGyqfn87ffDz_UXjN-vHHo8bUGBj_lqV48HxRUQ1eA-rEtq7uyioTpJnIA3LERd7M4AEwZY_ZqQk-yrcxuBnSH37UowsX7-alA1InV4u2AhdOVQKQE8mEmRlyy-27SGCVH3oRXoSruzzFl_eNuqx5dJvJqxUkQVMevqfkUiDHldz0hoJzjbz2_Ufg3Cifg8y4JY_5z2Uvbyx1-7iotYDETnlEy8LxN0o5QOPCyUr5OBtg2kwPmeKeQd3ShoLbDojRLouNgd8FGrLJPZWaTMkLNE1DzWTeC7ST36OF89Pi8xMwukho_XJz3bmKiIiAEMUHey5gtIhTT3YZiBQB6sumQj_IEcEb5p_00wlDLzJSD4KtHuY5SX__TkRDq0HETsm3ACSDmhZMfRqo4rNzuGps1KzboAx5aetHcct9J9CsFMfpExeawirvJI3aza8omT9xtrBL415XtOde0h_9Bnqq-FCd6Ht5FLhLvzEO6Ktphih1ytI9iVnEfCrDXkleqLqjedcOpHQhOR7OQVH53bQdJoMyL6LhZxHfwbdInYBEZeXmtkFOrqfrfkwgHNgKSuRHVmaP9BoZYJKpHtca8vJfX8xqicnS_3WwKJIBsrfgVpPOVWNqxUukmc0ZPrhyaFpKh6YNqeAxkEp80qBmJ2r3J_aMO8tivSmLZmhgakPXxkTER05owdgJGCB3SvQ4AKlqaFi1dDj9CaLCU33DM4YienvHfM2yor06uyBqeccrjQQR3cDSv8vnTPB9AydEqPvgc8jub9H6yEl9-fMFCIWitngmzUuPALe4w1DJkuadrakP0RX_LlbEvKBJ-xnpVCn0pWc1T6txc6BUoebeixUydxUQygHTJr5y_GZHNsKdqa1z80kKsT-EnDhX5CpQj5LUJoKIvVXXukpU1vkelXp5Aw2-WIVPrrUNMu-lf1t7duUu8nMgwjLl8Gy7dw8rcqIjmWbBma_tMKFa9DBo01_cHGM_dGBXustopAZaYLJF1RW05HFqwFwmPGc41McgPMMWhW1tt4ywxvAM5PGZPcMi3uKwEaaim-Rfw9XmcJ8j7JVAwv6JAinKv6Kj9HkyToXds7YeAP2uZHymSfpKQSe40h-1I-vbqmZ-0Lfi8HVy0I_iUUN46m5mCHmNmc-sfqowJXf6xAGQnNrDB0SttgA73dAPLbfejEJ4ENfrMvEdlwS91o5s6UP1x4LhKjfKnOGFvUSUzcqaeer6Uh85b8RX6TIFRcx7roVFXgqKql0_-3PMM7LCO_Mftz7mBo21NzS0FYfQTomZW3E_nMJc460c6JWdzg8JvGMpKvEJawSh507lGNA2Q2giS5MBRhqEOp8XZXsi6OxeyudEkEOcnf29xESNooYUFFvsWHrVHupc0brqUn8I0_y5EKMKjOG2StGMsqwyKMvhj4HvTNG6GV86k9KDNKSPROj6KytP3-TO0juwPQO3eOm2dJWWpM9sygP_BsJq9DxxPm3arcUmknXwYlC_AUMOqLL8N858Q3Af4f-oDRcbYe3ERZAeJ7D3n8xOAnRZPPFwryNNJpq2i4G9BCdzOKpj5oJwqs1FIF9BQtjwZIlnd6RkWbWeJQ-FTy1XFL_tFM_WBjwFdPoyksTry1tkrklExPnvWrDDpGhEBWaEXhDzs3L8rnf89TLMSyoZGFTJ3nfPTJ1q6kxkn1n0FqSQ4zVqA7QtDwOzyyrfX0IHdjmBYFUzPVtJG10e90yvBpTwBIN4TOCp4BFmdLtop0KN_hmRMQAzvhAUvOWR3QzSPlC7ZE-jjbW81fdeF9P2UWerpUIJoTReYWy39NwjeAO55wj1ynLnJmZgjvyQIJqa_fCsi02dzE4vMh3aZlQjEysGNct9fDRQE-TK2Y4rBNFmnbfgFfkoZcaFLuKCeMnjLulAWlXBKylLgnPu1GA25BCsyrb7U9vvywa4nTriRh8EJWu85QHUXXgGDT1RphiVmDLy0fw7SBvkH3WGrOFRmWgjtEZzTRjLKoS-lExtKoVsW_LYiTsJjPJdM2z3iy-GQyHhwzkLcTHV1sQ3gPrXW7MSBsEDl5fk8xYczN2vJxsrdfcQs321t38fKXDR-8R5oat1SQ7x8InOv1gEmSCub3hcz81IXT53V0yb_d-e7lv5UBFUDQG2iDteOSutMs1G2U0PhDltIlNQkDXLNXMj12A6IXfzjqVdxeLR9BKZ-Ru1gIOijDmuTsoL_3gA6_hKHlbrJwWjSAQPVH1d_tnHm3-RGnkt9buKLZDYYN4ZUliBCMajhDNnOXwExYx5OItfXrUzwmHDLztRZsyLJsiMLEKkG6S0HeFjGRaY1T-qxuifpuI0vOsukDckS16NyO6d0nS28Y2vgTS950zuA-FNWO47c9z7JoxYZizp5CuP81DLaxNH5uue9TaJWVhN0qtnI53E6aZtJBLts4gkXPp4Mfeieq0BbGfQKkzeqzL2RHA3HLFxpxL1ZCgmY60h84Vh8GekHNzieIltzeWTPok-j1I7_EuuxOkg_a-qcn-rFgma-GsK9pzZnglThkVu5Vv_t7mt4SD_njJNvP903S9yR_l36lXjdXT7VhTNj2WVgGFTNmrnu2Ixb96nGDVcBgymtdnToOCN1WwGtEASxmDTp_rlUZxNYN7x6PWnUuPT1Py_iFLST3_N8LZ0n-fGAdUKbOdpmDwF6Ye1W4tLyQwJ1d9jUhv0tx3g8dpSextLPhQ39U2vMFp5SfWHWAfdpRlHu63-5hnLiDUCN4u3Xmak1rzHEssyBEV0-i6vJZ825hCVW91jewCNhGSPdFTSv1fUDJwX4FhTeHjFIpWZmDumoGfAUCTBwb6fmUHc5tGbkPAWlaSMvknkOiNCbCTuMRiHQBjx0Cdic3GWDEq8xmQVP7uMiAWvikkvENBX2vxyapBWKOV04cMW1DQdE8Czezuz0lDqpBZ_mK0)