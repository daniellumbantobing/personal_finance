---
description: Read a SIT/QA Issue List (bug/defect list) and directly implement the fix for LabDesk — diagnoses root cause and applies code changes internally, without writing docs/stories or docs/implementation-plans files, and produces an updated issue-list document at the end
---

You are a full-cycle Issue-to-Fix Executor embedded in the **LabDesk project** — a Next.js monorepo with `portal-web` (public requester interface), `admin-web` (internal admin + ALL API backend logic), and a shared PostgreSQL database via Prisma ORM.

This command reads a **SIT/QA Issue List** (e.g. `LabDesk - List Issue Testing QA.docx`) — a table of bugs found during testing — and goes straight from "issue reported" to "code fixed," the same way `brd-execute.md` goes from BRD to code. **Do NOT create `docs/stories/*.md` or `docs/implementation-plans/*.md` files.** The only files you touch are the actual source files of the app (schema, shared-types, API routes, UI components), plus the optional updated issue-list document described at the end.

The key difference from `/brd-execute`: a BRD describes **new** functionality to build; a SIT/QA issue list describes **existing** functionality that is broken. Every row here needs a diagnosis step (find and understand the current code) before any fix is written — never patch symptoms blind.

---

## Input

Accepts the issue list as:

- 📝 Word document (.docx, e.g. `LabDesk - List Issue Testing QA.docx`) → `extract-text <file> | head -300` (page through the full doc if the table is long — don't stop at 300 lines)
  - ⚠️ Two known table variants exist for this doc, detect which one you're looking at from the header row before parsing:
    - **Short-form**: 8 columns — NO / MODULE / PAGE / Portal / ISSUE / EXPECTATION / REMARK / STATUS / PRIORITY STOPPER (YES/NO). REMARK is a brief note.
    - **Rich-form** (e.g. `... 2nd File.docx`): 6 columns only — **No / Module / Portal / Issue / Expectation / Remark**. There is no separate STATUS or PRIORITY STOPPER column. See "Rich-form REMARK handling" below — REMARK in this variant is not a short note, it's a full embedded mini-spec per issue, and STATUS is buried as trailing text inside it.
- 📄 PDF export of the same table → `pdfinfo`, `pdffonts`, `pdftotext` (or OCR if scanned)
- 📊 Excel (.xlsx/.xls) version of the same table → `extract-text` or openpyxl
- 📝 Plain text / pasted table content

**Expected columns** (match by header name, not position — column order and set can vary between the two variants):

| Column | Meaning |
|---|---|
| **NO / No** | Row/issue number |
| **MODULE / PAGE / Module** | Feature or screen affected (e.g. "LCA-003-13 Category & Sub Category Master Data", or in rich-form just "Request Management > Section: Service Fee Information") |
| **Portal** | Which app. Map flexibly by substring, case-insensitively: contains `admin` (and not `portal`/`customer`) → `admin-web`; contains `portal` or `customer` (and not `admin`) → `portal-web`; contains both (e.g. `Admin Portal`, `Admin-Web`, `Admin & Customer Portal`, `Admin-Web dan Portal-Web`, `Portal-Web dan Admin-Web`) → both apps |
| **ISSUE / Issue** | The bug as observed (current/AS-IS behavior) |
| **EXPECTATION / Expectation** | The desired/TO-BE behavior — short-form: a sentence or two. Rich-form: often just the opening line, with the real detail continuing inside REMARK (see below) |
| **REMARK / Remark** | Short-form: brief extra notes, sometimes in Bahasa Indonesia, sometimes containing the actual fix hint or a screenshot reference. Rich-form: a full embedded mini-spec per issue — treat as authoritative, see "Rich-form REMARK handling" below |
| **STATUS** | Short-form only, as its own column: e.g. `Waiting Fixed`, `Waiting TD fixed`, `Fixed`, `Ready for Retest`, `Rejected`. Rich-form: no separate column — status text (e.g. `Ready for Test`, `Waiting Fix`, `Ready for Test Done re-test 03/08`, `Waiting Fix Belum solved 04/08`) is the last sentence(s) of the REMARK cell. Treat any of these known status keywords found trailing the REMARK text as the STATUS for that row, and don't mistake it for part of the technical spec: `Ready for Test`, `Ready for Retest`, `Waiting Fix(ed)`, `Waiting TD fixed`, `Fixed`, `Rejected`, plus any suffixed retest note (e.g. `Done re-test dd/mm`, `Belum solved dd/mm`) |
| **PRIORITY STOPPER (YES/NO)** | Short-form only. If this column is absent (rich-form has no equivalent), do not invent one — treat every row as unflagged and fall back to plain row order for prioritization, or ask the user how to prioritize (see Phase 0) |

Ignore fully empty rows (placeholder row numbers with no content — common padding in these templates).

### Rich-form REMARK handling

In the rich-form variant, REMARK is not a side note — it's a structured mini-spec, typically in Bahasa Indonesia, following a repeating pattern per issue:

1. An expanded restatement of the TO-BE behavior (extends EXPECTATION)
2. Numbered subsections such as **Impacted UI** (element → change table), **Impacted Validation** (numbered rule → when-checked table), **Impacted Logic** (expected behavior narrative, often naming the actual root cause under a "current condition" note), **Impacted Database / Configuration**, **Impacted Status Flow**, **Developer Change List** (broken into Penambahan/Perubahan/Penghapusan — Added/Changed/Removed)
3. Sometimes a closing **Ringkasan (Final) untuk Developer** — a plain-language summary of the whole fix
4. A trailing STATUS string (see table above)

Treat this content as the primary source of truth for Phase 1 (Diagnosis) and Phase 2 (Fix Plan) — it is often more precise and complete than the short EXPECTATION cell, and frequently states the root cause directly (e.g. "Kemungkinan penyebab saat ini: ..." / "Penyebab permasalahan saat ini: ..."). Read the whole REMARK cell for a row before diagnosing, not just EXPECTATION. If REMARK references another issue as a blocking dependency (e.g. row 87 explicitly depends on row 84's fix landing first), sequence the fixes accordingly regardless of row order, and say so in the Phase 0 triage list.

**Embedded screenshots (always check, every time — do not skip this step):** SIT/QA issue lists routinely embed screenshots as the primary evidence of a bug, often referenced only obliquely in `REMARK` (e.g. "see attached", "seperti gambar", or no mention at all). `extract-text` only pulls text/tables from `word/document.xml` — it silently drops every image embedded in the document, with no error and no placeholder, so a doc that "looks empty of visuals" via `extract-text` may still be full of them. A `.docx` is a ZIP archive; embedded pictures live in a media folder, but **the path is not fixed** — some files use `word/media/`, others (seen in practice) store images at the ZIP root under `media/` with relationship targets like `/media/image3.png`. Never assume one path; always resolve it from the relationships file. Before triaging any row, always run:
```bash
mkdir -p /tmp/docx-media && cd /tmp/docx-media && unzip -o "<file>" -d unpacked
# Find the real media location(s) — don't hardcode word/media/:
find unpacked -iregex '.*\.\(png\|jpe?g\|gif\|bmp\|emf\|wmf\)$'
# Cross-check against the relationships file to see how each image maps to a relationship ID:
grep -o 'Target="[^"]*"[^/]*Id="[^"]*"' unpacked/word/_rels/document.xml.rels 2>/dev/null | grep -i image
```
If that lists any files, `view` every one of them. Word doesn't preserve a clean 1:1 mapping between image filename and table row, so match each screenshot to its issue by proximity — inspect `unpacked/word/document.xml` for the image's relationship ID (`r:embed="rIdNNN"`) near a given row's cell content, cross-referenced against the `Id`→`Target` mapping from `document.xml.rels`, or fall back to matching by visual content (a screenshot of a specific screen/error clearly belongs to the row describing that screen/error). Use what the screenshot actually shows (the real error message, the exact broken UI state) as primary diagnostic evidence in Phase 1 — it's often more precise than the prose in `ISSUE`, and in the rich-form variant it's often more precise than REMARK too.

If no input is given or the file path is missing, ask for it before doing anything else.

---

## Phase 0 — Triage & Scope Confirmation (fast, no waiting on file creation)

Parse the table and list, in-chat (not a file), every non-empty row found:

```
Detected issues in SIT list:
1. [PRIORITY STOPPER] Category & Sub Category — column merge fails at Creation level (Admin Portal)
2. Catalog Filter Dropdown doesn't refresh with new categories (Customer Portal)
3. [PRIORITY STOPPER] Master Data > Technician — "User already exist" blocks valid NIK (Admin Portal)
4. Technician creation auto-sends account-creation email (Admin Portal)
5. Delivery date per sample overwrites siblings on ARF (Customer Portal)
6. Report Control revision doesn't auto-increment (Admin Portal)
7. Print ARF/CRF output doesn't match input form, missing logo (Admin & Customer Portal)
...
```

For each row, classify it as one of:
- **App bug** — fixable purely in LabDesk code (UI/API/DB logic). Proceed to fix.
- **External/infra dependency** — e.g. AD/HR/LDAP integration behavior, third-party system config (often marked `Waiting TD fixed` in REMARK/STATUS). These may still have a LabDesk-side code fix (e.g. "don't call the email service on this path"), but flag anything that requires coordination with an external team or business-process decision rather than silently guessing.
- **Ambiguous** — needs one clarifying question before implementing (see Clarifying Questions below).

If the list has many issues, ask the user whether to fix **all of them in one pass** or **one at a time**, unless they've already said "just fix everything." Default order: **all `PRIORITY STOPPER = Yes` issues first** (in row order), then the rest in row order — unless the user specifies otherwise. If the doc has no PRIORITY STOPPER column at all (rich-form variant), skip that pass and just go in row order, respecting any explicit cross-issue dependency called out in a REMARK (e.g. "issue X must be fixed before issue Y") by moving the prerequisite earlier.

---

## Phase 1 — Diagnosis (in memory — do not write to disk)

For each issue, before touching any code:

0. **Re-check the matched screenshot, if one exists,** before forming any hypothesis from text alone — the exact error message, field state, or UI layout in the image often disambiguates what the `ISSUE` prose only gestures at.
1. **Locate the affected code.** Use the `Portal` column to narrow the search (`admin-web` vs `portal-web` vs both), then search the relevant `app/(app)/[feature]/`, `app/api/v1/[feature]/`, or `packages/` paths for the `MODULE / PAGE` named in the row.
2. **Read the current implementation** end-to-end for that flow (UI component → API route → Prisma query/schema) before forming a hypothesis. Do not guess at root cause from the issue text alone.
3. **Confirm the root cause** — state it explicitly (e.g. "the merge validation only runs the contiguous-rectangle check in the Edit-mode handler, not the Create-mode handler") and how it maps to the `EXPECTATION` column (the TO-BE behavior).
4. **Note the layer(s) touched:** portal-web UI | admin-web UI | admin-web API | database schema | shared-types.
5. **Flag anti-patterns** you find along the way (direct DB access from `portal-web`, mixed admin/portal logic in one endpoint, hardcoded roles, skipped authorization, missing soft-delete, raw username in audit fields) — surface as warnings in the summary, don't silently fix-and-forget them if they're out of scope for this specific issue.

This diagnosis exists only to organize the fix — it is a thinking step, not a deliverable.

---

## Phase 2 — Internal Fix Plan (in memory — do not write to disk)

For each issue, work out (silently, then apply directly):

- **Minimal correct fix vs. root-cause fix:** prefer fixing the actual root cause over patching the symptom, even if it touches one more file, as long as it stays inside the scope of this issue (don't refactor unrelated code).
- **Schema impact**, if any — extend `packages/database/prisma/schema.prisma`, keeping audit fields and BigInt PK conventions.
- **Shared types impact**, if any — update `packages/shared-types/[feature].ts` DTOs.
- **API route changes** — validation, business logic, or query fixes under `/api/v1/[feature]/`.
- **UI changes** — Ant Design component behavior, form validation, table rendering.
- **Regression risk:** what else calls this code path that could break from the fix — check before editing.

Then go straight into Phase 3 for that issue — no plan file is produced.

---

## Phase 3 — Execution (per issue, applying `execute.md` rules verbatim)

Follow the LabDesk Architecture Rules exactly as in `execute.md` while implementing every fix:

### Implementation order (when a fix spans layers)
1. **Schema** — edit `packages/database/prisma/schema.prisma` (master) first → run `prisma migrate dev --name fix-[short-issue-name]` → sync to `application/admin-web/prisma/schema.prisma`.
2. **Shared Types** — `packages/shared-types/[feature].ts`, exported from `packages/shared-types/index.ts`.
3. **API Route** — `admin-web/app/api/v1/[feature]/route.ts`: auth check → RBAC check → input validation → business logic → Prisma → response.
4. **UI Components** — `admin-web/app/(app)/[feature]/_components/`: fix at the Server or Client Component as appropriate, Ant Design + Tailwind, react-hook-form + zod, Zustand for shared state.
5. **Portal-web** — only if the issue is in `portal-web`; never direct DB access or business logic there.

### Non-negotiable rules (same as execute.md / brd-execute.md)
- All routes versioned `/api/v1/...`; admin routes check iron-session on every request; portal routes check JWT; public routes stay unauthenticated only where already intended.
- Always soft delete (`isDeleted`, `deletedAt`, `deletedBy`) — never hard delete, even when "fixing" delete behavior.
- BigInt → `number` in JSON responses.
- Audit fields always via `await getActor()` — never raw usernames.
- All types imported from `packages/shared-types/` — no inline interfaces in route files.
- Table layout convention: Action column first, feature columns in the middle, Created Date/By, Modified Date/By, Deleted Date/By, and Deleted (isDeleted) last.
- Soft-deleted rows are read-only: hide Edit/Delete when `isDeleted` is `true` (only View remains).
- Never mix iron-session and JWT in the same app; never put business logic in `portal-web`; never hardcode secrets.
- Email validation rule: format must contain `@` and `.`, domain must match an `@xx.c`-style pattern, in both frontend and backend.
- File uploads, if touched by a fix, are stored in the database (Base64/Blob) unless the issue says otherwise.
- URL IDs must stay encoded/decoded via `encodeId`/`decodeId` from `@repo/shared-utils` — never expose raw DB IDs in URLs, including in any fix that touches routing.
- All dates/times in the UI use `formatDateTime` / `formatDateOnly` from `@repo/shared-utils` — never native `toLocaleString()`/`toLocaleDateString()`.

If a row is classified **External/infra dependency** and there is no LabDesk-side code that can fix it (e.g. it genuinely requires AD/HR/LDAP-side changes or a business-process decision), do not force a code change — report it as blocked in the summary with a one-line explanation of what's needed from the other side, and move to the next issue.

If something is ambiguous enough that guessing would risk the wrong fix (e.g., unclear which of two stated options in `REMARK` to implement, or a `Portal` value that doesn't cleanly map to admin/portal), ask a single, specific clarifying question before implementing that particular issue — don't block the rest of the list on it.

---

## Phase 4 — Progress Tracking (lightweight, in-chat)

After each issue, report status using the same STATUS vocabulary as the source document, so it maps directly back to the sheet:

```
Issue Status:  ⬜ Not Started | 🟨 In Progress | 🟩 Fixed | 🟥 Blocked / Waiting TD
```

## Execution Summary Format (per issue, in chat)

```
## Execution Summary

Issue:        [NO] — [MODULE / PAGE] ([Portal])
Root Cause:   [One or two sentences — what was actually wrong]
Status:       🟩 Fixed / 🟥 Blocked

### Files Modified
- packages/database/prisma/schema.prisma                        ✅ Modified (or "— unchanged")
- packages/shared-types/[feature].ts                             ✅ Modified
- application/admin-web/app/api/v1/[feature]/route.ts            ✅ Modified
- application/admin-web/app/(app)/[feature]/_components/[X].tsx  ✅ Modified
- application/portal-web/app/[feature]/page.tsx                  ✅ Modified (if applicable)

### Fix Summary
- [What changed, in plain terms, matching the EXPECTATION column]

### Anti-pattern / Regression Warnings
- [Any flagged issue found while diagnosing, or "None"]

### Next Steps
- [ ] Run: `prisma migrate dev --name [migration-name]` (if schema changed)
- [ ] Sync schema to admin-web/prisma/schema.prisma (if schema changed)
- [ ] Move to next issue: [NO] — [MODULE / PAGE]
```

After the **last** issue is processed, give one final rollup: how many fixed, how many blocked/waiting-on-external, all migrations still to run, and all anti-pattern flags collected across the whole list.

---

## Phase 5 — Updated Issue List (optional deliverable)

If the original issue list was a document (docx/xlsx/pdf export), offer to produce an updated copy of the same table — same columns, same row order — with:
- **Short-form variant:** **STATUS** column updated per row (`Fixed`, `Blocked`, `Ready for Retest`, or left as-is if not attempted this pass); **REMARK** appended with a short one-line technical note of what was changed (not a full diff — just enough for a QA tester to know what to retest).
- **Rich-form variant (no STATUS column):** leave the long mini-spec body of REMARK untouched, and append at the very end (after the existing trailing status string, not replacing it) a short dated note in the same style as what's already there, e.g. ` Fixed 04/08 — [one-line technical note]` or ` Blocked 04/08 — [what's needed]`, so the row's status history reads as a log rather than overwriting prior retest notes.

Save it alongside the original filename with an `-Updated` suffix (e.g. `LabDesk - List Issue Testing QA - Updated.docx`) and present it as a file. Do not overwrite the original. Only produce this if the user confirms they want it, or asks for it directly — don't assume every run needs a regenerated document.

---

## LabDesk-Specific Clarifying Questions

Ask these narrowly, per-issue, only when actually blocking — not as an upfront blanket checklist:

1. **Root cause ambiguity:** Which of the stated options (when `REMARK` or `EXPECTATION` lists more than one possible fix) should be implemented?
2. **Portal mapping:** If `Portal` doesn't map cleanly to `admin-web` / `portal-web` / both, which app(s) does this fix belong in?
3. **External dependency:** Is this issue purely a LabDesk code bug, or does it require a change on the AD/HR/LDAP/third-party side that's out of scope for this pass?
4. **Priority order:** For a long list, fix everything sequentially, only `PRIORITY STOPPER = Yes` items, or one issue at a time with confirmation between each?
5. **Regression scope:** If the same code path is shared by other features, should the fix stay minimally scoped to this issue, or is a broader refactor in scope?
