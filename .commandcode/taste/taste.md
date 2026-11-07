# Taste

- Requires an approval-gated workflow: start with a read-only audit/plan, make no source-code, schema, or data changes (and no destructive commands like `migrate:fresh` / `db:wipe`) until they explicitly approve the plan and design. Confidence: 0.85
- Wants existing applications extended in place — preserve the current UI design, components, and business logic rather than rebuilding from scratch or swapping in a new template. Confidence: 0.7
- Prefers minimal scope and complexity: no unrequested feature modules (roles/permissions, payment gateways, extra auth systems, a second frontend app) and no unnecessary dependencies — explicitly rules these out even when a spec section hints at them. Confidence: 0.6
- Doesn't want the test suite run repeatedly (`php artisan test`) as part of each slice; verify behavior through real DB queries and HTTP/browser-style checks instead. Confidence: 0.65
