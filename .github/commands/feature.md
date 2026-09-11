---
description: Start a new feature enhancement based on project standards
---

Before writing any code, re-read and re-internalize the full GEMINI.md 
file in this project root.

Then follow this checklist:
1. Confirm which app this feature belongs to (portal-web / admin-web)
2. Confirm the affected API endpoints under admin-web/app/api/
3. Confirm if database schema changes are needed (edit master schema only)
4. Confirm shared types needed in packages/shared-types/
5. Use TypeScript always — no plain JavaScript
6. Use BigInt for all ID fields
7. Use soft delete (isDeleted = true), never hard delete
8. Use getActor() from lib/audit.ts for all audit columns
9. Return consistent API response: { success, data, message }
10. Write the implementation plan before generating any code

Now proceed with the following feature request: