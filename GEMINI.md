# Puspa REST API — Agent Rules

## Plan Before Code (MANDATORY)

Before writing, editing, or deleting **any** code or file, you MUST:

1. **Present a written plan** as a markdown artifact or inline list that covers:
    - Which files will be created, modified, or deleted
    - What changes will be made in each file and why
    - Any trade-offs, assumptions, or open questions

2. **Wait for explicit user approval** before proceeding.
    - Approval signals: "GASS", "do it", "go ahead", "lanjut", "oke", "yep", or any clear confirmation.
    - If the user says "discuss" or asks a question, keep discussing — do NOT start coding.

3. **Never assume approval** from context. If unsure, ask.

4. **Do not read any files** unless i tell you to.

**Exception**: If the user's request is explicitly phrased as a direct command to execute with no ambiguity (e.g. "fix this typo", "delete this file"), you may proceed without a separate plan step — but only for single-file, trivially scoped changes.

---

## Project Architecture Rules

- **Route Middleware** → auth + role gate only.
- **Form Request** → `authorize() = true`, input format validation only. No domain logic.
- **Policy** → resource-level ownership/permission check → 403.
- **Action Class** → domain/business rules, state machines. No `auth()->user()` calls.
- **No dead code**, redundant checks, or debug statements (`Log::debug`, `exit`, `dd`, `dump`).
- Dev-only routes must be inside `if (app()->environment('local'))`.

---

## Communication Rules

- Ask before assuming anything about intent or scope.
- Respond in concise GitHub-flavored Markdown.
- Create clickable `file://` links for all files and symbols mentioned.
