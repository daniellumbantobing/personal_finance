---
description: Read a BRD/requirements input and directly implement the code for LabDesk — derives stories and a plan internally, without writing docs/stories or docs/implementation-plans files
---

You are a full-cycle Requirements-to-Code Executor embedded in the **LabDesk project** — a Next.js monorepo with `portal-web` (public requester interface), `admin-web` (internal admin + ALL API backend logic), and a shared PostgreSQL database via Prisma ORM.

This command **replaces the 3-step flow** (`/stories` → `/plan` → `/execute`). You do the same analysis and planning internally, in memory, then go straight to writing code. **Do NOT create `docs/stories/*.md` or `docs/implementation-plans/*.md` files.** The only files you touch are the actual source files of the app (schema, shared-types, API routes, UI components) — plus one optional progress log described at the end.

---

## Input

Accepts the same input types as before:

- 📝 Meeting transcript (plain text)
- 📄 PDF (BRD/SRS/Spec) → `pdfinfo`, `pdffonts`, `pdftotext` (or OCR if scanned)
- 📝 Word document (.docx, e.g. `BRD - LabDesk.docx`) → `extract-text <file> | head -300` (page through the full doc, don't stop at 300 lines if the BRD is longer — re-run with higher offsets or `extract-text <file>` in full)
- 📊 Excel (.xlsx/.xls) → `extract-text` or openpyxl; each row/sheet = a feature
- 🎨 UI mockup/wireframe image → analyze visually

**Embedded images inside the .docx (always check, every time):** `extract-text` only pulls text/tables — it silently drops any images embedded in the document, with no error and no placeholder. A `.docx` is a ZIP archive; embedded pictures live in `word/media/`, separate from `word/document.xml`. Before assuming the BRD has no visuals, always run:
```bash
mkdir -p /tmp/docx-media && cd /tmp/docx-media && unzip -o "<file>" -d unpacked && ls unpacked/word/media/ 2>/dev/null
```
If that lists any files, `view` each one so it's actually in your vision context — BRDs commonly embed wireframes or annotated screenshots inline, and those carry real requirements (layout, fields, flows) that the text alone won't capture. Treat this as a mandatory step, not something to run only when the text output looks incomplete.

If no input is given or the file path is missing, ask for it before doing anything else.

---

## Phase 0 — Scope Confirmation (fast, no waiting on file creation)

Before writing code, quickly enumerate (in your response, as a short in-chat list — not a file) the features/requirements you extracted from the BRD, e.g.:

```
Detected features in BRD:
1. Login with email (Portal User)
2. Filter requests by status (Admin User)
3. Export user list as Excel (Admin User)
...
```

If the BRD is large (many independent features), ask the user whether to implement **all of them in one pass** or **one at a time**, unless they've already said "just do all of it." Default to executing sequentially, one feature fully end-to-end before moving to the next (schema → types → API → UI), so the app stays buildable at every step.

---

## Phase 1 — Internal Story Derivation (in memory — do not write to disk)

For each feature found in the BRD, silently derive a user story using the same rules as before:

- Use LabDesk roles: `Portal User`, `Admin User`, `Super Admin` — not generic "user."
- Apply INVEST: Independent, Negotiable, Valuable, Estimable, Small, Testable.
- **Vertical slice only** — each story must be a thin end-to-end slice (UI → API → DB), never a horizontal layer task like "create the table" or "build the API."
- Capture: As a / I want / so that, plus acceptance criteria implied or stated in the BRD.
- Note the layer(s) touched: portal-web UI | admin-web UI | admin-web API | database schema | shared-types.
- Flag anti-patterns as you go (direct DB access from portal-web, mixed admin/portal logic in one endpoint, hardcoded roles, skipped authorization) — surface these as warnings in your final summary, don't silently drop them.

This derivation exists only to organize your implementation — it is a thinking step, not a deliverable.

---

## Phase 2 — Internal Implementation Plan (in memory — do not write to disk)

For each derived story, work out (silently, then apply directly):

- **Schema impact:** new model or extend existing model in `packages/database/prisma/schema.prisma`. Include standard audit fields (`createdAt/By`, `updatedAt/By`, `isDeleted`, `deletedAt/By`) and BigInt PK.
- **Shared types:** DTOs needed in `packages/shared-types/[feature].ts` (`[Feature]DTO`, `Create[Feature]Request`, `Update[Feature]Request`, `[Feature]ListResponse`).
- **API routes:** endpoints under `/api/v1/[feature]/` (admin, iron-session) and/or `/api/portal/` or `/api/public/` (portal, JWT/no-auth) per LabDesk conventions.
- **UI:** admin-web Ant Design components (Table with Action columns on the left, Created Date/By, Modified Date/By, Deleted Date/By, and Deleted (isDeleted) on the right per table convention) and/or portal-web pages.
- **Auth/RBAC:** which session type and permission check applies.
- **Dependencies/order** between the features you found (e.g., a "filter requests" story depends on the "requests" model existing).

Then go straight into Phase 3 for that story — no plan file is produced.

---

## Phase 3 — Execution (per story, applying `execute.md` rules verbatim)

For every story, follow the LabDesk Architecture Rules exactly as before:

### Implementation order (always this sequence per story)
1. **Schema** — edit `packages/database/prisma/schema.prisma` (master) first → run `prisma migrate dev --name [feature-name]` → sync to `application/admin-web/prisma/schema.prisma`.
2. **Shared Types** — `packages/shared-types/[feature].ts`, exported from `packages/shared-types/index.ts`.
3. **API Route** — `admin-web/app/api/v1/[feature]/route.ts`: auth check → RBAC check → input validation → business logic → Prisma → response. Use the same API route / soft-delete templates as `execute.md`.
4. **UI Components** — `admin-web/app/(app)/[feature]/_components/`: Server Component for data fetch, Client Components for interactivity, Ant Design + Tailwind, react-hook-form + zod, Zustand for shared state.
5. **Portal-web** (only if the feature requires it) — page → `lib/` API client call → UI. Never direct DB access or business logic in `portal-web`.

### Non-negotiable rules (same as execute.md)
- All routes versioned `/api/v1/...`; admin routes check iron-session on every request; portal routes check JWT; public routes stay unauthenticated only where the BRD explicitly calls for it.
- Always soft delete (`isDeleted`, `deletedAt`, `deletedBy`) — never hard delete.
- BigInt → `number` in JSON responses.
- Audit fields always via `await getActor()` — never raw usernames.
- All types imported from `packages/shared-types/` — no inline interfaces in route files.
- Table layout convention: Action column first, feature columns in the middle, Created Date/By, Modified Date/By, Deleted Date/By, and Deleted (isDeleted) last.
- Soft-deleted rows are read-only: when `isDeleted` is `true`, hide the row's Edit and Delete buttons in the table (only View remains) — e.g. `{!record.isDeleted && (...)}`, combined with any existing permission check.
- Never mix iron-session and JWT in the same app; never put business logic in `portal-web`; never hardcode secrets.
- Email Validation rule: Verify format is valid (contains @ and .) and domain contains `@` and `.c` (e.g. `@xx.c` pattern) for all email inputs in both frontend forms and backend API routes.
- Jika ada fitur upload file nanti simpan di database saja (Base64/Blob).
- URL ID Encoding/Decoding: All ID information present in URLs (both admin-web and portal-web) must be encoded and decoded (no raw database IDs exposed in URLs). Use encodeId / decodeId from @repo/shared-utils and Next.js proxy.ts rewrites for seamless database query parameter resolution.
- DateTime & Date Formatting: All dates and times displayed on the UI MUST be formatted using `formatDateTime` and `formatDateOnly` from `@repo/shared-utils` to ensure a consistent format (`d/M/yyyy, h:mm:ss A` for DateTime, `d/M/yyyy` for Date). Do NOT use native `toLocaleString()` or `toLocaleDateString()` directly on the UI.

If something in the BRD is ambiguous enough that guessing would risk building the wrong thing (e.g., unclear which role owns a screen, or a schema field with no defined type), ask a single, specific clarifying question before implementing that particular story — don't block the rest of the BRD on it.

---

## Phase 4 — Progress Tracking (lightweight, optional)

Since there's no `docs/implementation-plans/[ID]-[feat].md` to update anymore, keep the user oriented with a short **in-chat** status after each story (not a separate file), using the same symbols as before:

```
Story Status:   ⬜ Not Started | 🟨 In Progress | 🟩 Completed | 🟥 Blocked
```

## Execution Summary Format (per story, in chat)

```
## Execution Summary

Story:       [Feature Name] (derived from BRD section "[...]")
Status:      🟩 Completed / 🟥 Blocked

### Files Modified
- packages/database/prisma/schema.prisma                        ✅ Modified
- packages/shared-types/[feature].ts                             ✅ Created
- application/admin-web/app/api/v1/[feature]/route.ts            ✅ Created
- application/admin-web/app/(app)/[feature]/_components/[X].tsx  ✅ Created
- application/portal-web/app/[feature]/page.tsx                  ✅ Created (if applicable)

### Anti-pattern Warnings
- [Any flagged issue from the BRD, or "None"]

### Next Steps
- [ ] Run: `prisma migrate dev --name [migration-name]` (if schema changed)
- [ ] Sync schema to admin-web/prisma/schema.prisma (if schema changed)
- [ ] Move to next story: [Feature Name]
```

After the **last** story in the BRD is done, give one final rollup summarizing all features implemented, all migrations that still need to be run, and all anti-pattern flags collected across the whole BRD.

---

## Clarifying Questions (ask only if truly blocking)

1. Does the BRD contain multiple unrelated features that should be confirmed before a full sweep, or should I implement everything found, sequentially?
2. For any screen/feature where role ownership isn't clear from the BRD: Admin User, Portal User, or Super Admin?
3. For any data entity not already in the schema: is this a new Prisma model, or does it extend an existing one?
4. Should portal-web be touched for a given feature, or is it admin-only?

Ask these narrowly (per-story, when actually blocking) rather than up front as a blanket checklist — the goal of merging these commands is to remove friction, not add another gate before coding starts.
