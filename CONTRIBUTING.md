# Contributing to SVCI Online Document System

## Branch Naming

Use the following format:

```
<type>/<short-description>
```

| Type | Use for |
|------|---------|
| `feat/` | New features |
| `fix/` | Bug fixes |
| `refactor/` | Code refactoring without behavior change |
| `test/` | Adding or updating tests |
| `chore/` | Tooling, CI, dependencies, config |
| `docs/` | Documentation only |

**Examples:**
- `feat/student-request-submission`
- `fix/payment-upload-validation`
- `chore/add-eslint-config`
- `test/clearance-policy-unit`

Always branch off `main` (or `develop` if the project has a staging branch).

## Commit Format

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <short description>

[optional body]

[optional footer]
```

**Types:** `feat`, `fix`, `refactor`, `test`, `chore`, `docs`, `perf`, `style`

**Scope** is optional but helpful — use the feature area: `auth`, `student`, `admin`, `department`, `superadmin`, `payment`, `clearance`, `notifications`, `realtime`, `ci`, `docker`.

**Examples:**

```
feat(student): add document request submission form

Implement the StoreRequestRequest form request and RequestService.createRequestBatch().
Dispatches RequestSubmitted event on success.
```

```
fix(payment): reject non-image uploads for payment receipts

Added MIME type validation to PaymentUploadRequest.
Resolves a security issue where any file type could be uploaded.
```

```
test(clearance): add unit tests for ClearancePolicy
```

```
chore: add phpstan level 6 config
```

## Pull Request Template

When opening a PR, use this template:

```markdown
## Summary

<!-- 1–3 bullet points describing what changed and why -->

## Changes

- [ ] Task from phase plan (link if applicable)
- [ ] Tests added/updated
- [ ] No regressions in existing tests

## Testing

<!-- Describe how you tested this. -->

- [ ] `./vendor/bin/pest` passes
- [ ] `npm run lint` passes
- [ ] `./vendor/bin/pint --test` passes
- [ ] `./vendor/bin/phpstan analyse` passes
- [ ] Manually tested in browser

## Security Considerations

<!-- If this touches auth, uploads, or sensitive data, note any security implications. -->

## Screenshots (if UI changes)

<!-- Add before/after screenshots if there are visual changes -->
```

## Code Standards

- **PHP:** Follow the Laravel preset via Pint (`./vendor/bin/pint`). Run before committing.
- **Vue/JS:** ESLint + Prettier via `npm run lint:fix` and `npm run format`.
- **Testing:** Write tests first (TDD). Minimum 80% coverage on business logic.
- **Architecture:** Thin controllers → services. Validation in Form Requests. Authorization in Policies.

## Local Setup

See [README.md](./README.md) for local development setup instructions.

## Review Process

1. Open a PR against `main` (or `develop`).
2. CI must be green (Pint + PHPStan + Pest + ESLint).
3. At least one peer review approval required.
4. Address all review comments.
5. Squash merge with a clean commit message.
