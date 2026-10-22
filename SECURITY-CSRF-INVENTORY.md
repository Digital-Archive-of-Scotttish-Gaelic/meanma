# Phase 3A request and CSRF inventory

This inventory records the server-side classification used by the Phase 3A containment. It does not change existing role, group, ownership, or account semantics.

## Application entry points

| Entry point | Read-only requests | State-changing requests |
| --- | --- | --- |
| `index.php` | Authenticated controller browse, view, edit-form, search, frequency, and generation pages; public login and recovery forms | Login, logout, recovery request and password replacement; `corpus:save`, `writers:save`, `collection:add`, and `slips:add` |
| `ajax.php` | Actions listed below as read-only | Actions listed below as state-changing |
| `printSlip.php` | Authenticated PDF generation | None |
| `lemmaEdit.php` | Authenticated lemma editor page | Writes are dispatched through `ajax.php?action=editLemma` |
| `populateDates.php` | None over HTTP | Superuser maintenance write; POST and CSRF required |
| `populateWordforms.php` | None over HTTP | Superuser maintenance write; POST and CSRF required |
| `populateTextDB.php` | Authenticated maintenance read | None in the current implementation |
| `_insertCitations.php` | Authenticated dormant maintenance page | The historical write block is commented out and does not execute |
| Corpus parser, lemmatiser, frequency builder, and old CSV utility | Command-line only | Web requests remain rejected |

Direct HTTP access to internal controller, model, view, test, dependency, documentation, XSL, and utility directories remains denied by the Phase 2 web-server rules.

GET-based slip edit views now require an existing slip identifier so their model constructors cannot fall through to implicit record creation. New-slip creation remains a protected POST operation.

## `ajax.php` read-only actions

`msPopulateModal`, `msGetEditionHtml`, `getUsername` (disabled), `getSlips`, `getCitationsBySlipId`, `loadTranslation`, `loadSlipData`, `loadSenseData`, `getSlipLinkHtml`, `getDictionaryResults`, `getGrammarInfo`, `getSlowSearchResults`, `getSenseInfo`, `search`, `xsearch`, `getCombinedMetadata`, `getResultContext`, and `getFrequencies`.

These actions retain their existing request methods. `getDictionaryResults` and `getCombinedMetadata` use POST to carry read parameters but do not change server state.

## `ajax.php` state-changing actions

Every action below requires POST and a valid session-bound `application` CSRF token before the shared database object is created:

- Session and preferences: `updatePrintList`, `loadSlip`, `setGroup`.
- Citations and translations: `getContext`, `loadCitation`, `saveCitation`, `deleteCitation`, `createTranslation`, `saveTranslation`, `deleteTranslation`.
- Slips, entries, piles, and senses: `deleteSlips`, `deleteEntries`, `createPaperSlip`, `getPileCategoriesForNewWordclass`, `saveSlip`, `addTextIdToSlip`, `autoCreateSlips`, `saveSlipPile`, `addPile`, `editPile`, `createEntry`, `saveEntry`, `saveSense`, `swapSense`, `unassignSense`.
- Lemma and grammar data: `saveLemmaGrammar`, `editLemma`.
- Issues and notifications: `requestUnlock`, `raiseIssue`, `updateIssue`.
- Emendations and deletions: `createEmendation`, `updateEmendation`, `deleteEmendation`, `createDeletion`, `updateDeletion`, `deleteDeletion`.

Some legacy action names begin with `get` or `load` but are state-changing: `getContext` can attach a citation, `loadCitation` creates a citation when passed the new-item sentinel, `getPileCategoriesForNewWordclass` changes a slip and entry, and `loadSlip` changes session result state. They are therefore protected as mutations.

## Authentication and recovery

- Login, logout, recovery requests, and password replacement require POST and their existing purpose-specific CSRF tokens.
- Login and logout tokens are consumed on successful validation.
- All pre-authentication CSRF tokens are discarded after successful login; fresh application and logout tokens are then issued. Tokens are destroyed with the session on logout.
- Recovery-link display remains GET and retains the Phase 2 account-bound, expiring, single-use recovery authorization.
- Wrong methods for known authentication actions are rejected before controller execution.

## Preserved authorization ambiguities

Phase 3A deliberately does not invent new role rules. The following pre-existing semantics should receive owner review in a later authorization-focused increment:

- Citation and translation deletion contain commented-out superuser checks and currently remain available to any authenticated user.
- Writer and corpus save forms are shown selectively by parts of the UI, but the controllers do not independently impose a new role check.
- Several slip, entry, pile, sense, lemma, issue, emendation, and deletion mutations rely on existing UI visibility, ownership, lock, group, or controller behavior rather than a uniform server-side role policy.
- `populateTextDB.php` and the dormant `_insertCitations.php` do not express an explicit role policy; no new permission meaning was assigned in this increment.

CSRF and method enforcement applies regardless of these existing permission semantics. Existing Phase 2 authentication, group-membership validation, and explicit superuser checks remain in force.

## Manual browser acceptance

Before production deployment, exercise the following with isolated data and representative existing roles/groups:

- Create, open, edit, save, lock/unlock-request, print-list, and delete slip workflows, including the new-tab create-slip POST navigation.
- Citation, translation, emendation, deletion, pile, sense, entry, lemma, and lemma-grammar editing.
- Corpus metadata and writer form saves.
- Group switching and role-restricted delete operations.
- Login, logout, recovery email delivery, recovery-link display, and password replacement.

The shared jQuery prefilter changes legacy mutation calls from GET to POST and adds the session token. A `403 invalid_csrf_token` response is surfaced with a reload-and-retry message. Browser acceptance is required because the repository has no JavaScript DOM integration harness for these legacy UI paths.
