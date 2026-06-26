# 15 — Testing Strategy

## Goals

- **80%+ test coverage** on business logic (services, policies, key controllers).
- All critical user flows covered by feature tests.
- Browser tests for the most important happy paths.
- Tests run in CI on every PR.

## Test Pyramid

```
         ▲
        ╱ ╲     E2E / Browser tests (~10)
       ╱   ╲    Critical flows: public request, tracking, admin validation, clearance
      ╱─────╲
     ╱       ╲  Feature / HTTP tests (many)
    ╱         ╲ Controller endpoints, policies, validation
   ╱───────────╲
  ╱             ╲ Unit tests (most)
 ╱               ╲ Services, helpers, model methods
╱─────────────────╲
```

## Tools

| Tool | Purpose |
|------|---------|
| **Pest 3** | Unit + feature tests (PHPUnit under the hood) |
| **Laravel Dusk** or **Playwright** | Browser tests |
| **Mockery** | Mocking dependencies |
| **Faker** | Test data generation |
| **Larastan** | Static analysis |
| **Laravel Pint** | Code style |

Choosing **Playwright** over Dusk for browser tests because:
- Faster, more reliable
- Better debugging tools
- TypeScript-first, matches our Vue side

## Directory Structure

```
tests/
├── Pest.php                  (global test config)
├── TestCase.php
├── Unit/
│   ├── Services/
│   │   ├── RequestServiceTest.php
│   │   ├── PaymentServiceTest.php
│   │   ├── ClearanceServiceTest.php
│   │   └── PdfServiceTest.php
│   ├── Models/
│   │   ├── UserTest.php
│   │   └── ClearanceTest.php
│   └── Policies/
│       ├── DocumentRequestPolicyTest.php
│       └── ClearancePolicyTest.php
├── Feature/
│   ├── Auth/
│   │   ├── RegistrationTest.php
│   │   ├── LoginTest.php
│   │   └── PasswordResetTest.php
│   ├── Public/
│   │   ├── PublicRequestSubmissionTest.php
│   │   ├── PublicTrackingTest.php
│   │   └── PublicRequestValidationTest.php
│   ├── Student/
│   │   ├── DashboardTest.php
│   │   ├── RequestSubmissionTest.php
│   │   ├── PaymentUploadTest.php
│   │   └── ClearanceViewTest.php
│   ├── Admin/
│   │   ├── RequestApprovalTest.php
│   │   ├── PaymentApprovalTest.php
│   │   └── DocumentTypeManagementTest.php
│   ├── Department/
│   │   └── ClearanceSigningTest.php
│   └── SuperAdmin/
│       ├── UserApprovalTest.php
│       └── UserManagementTest.php
└── Browser/  (Playwright)
    ├── auth.spec.ts
    ├── public-request-flow.spec.ts
    └── admin-flow.spec.ts
```

## Database Strategy

- Use `RefreshDatabase` trait — fresh schema per test class.
- Use **factories** for all model creation.
- Use **seeders** for shared baseline data (roles, document types).
- Test against MySQL (not SQLite) to match production behavior.

## Example: Unit Test (Service)

```php
// tests/Unit/Services/RequestServiceTest.php
use App\Models\{User, DocumentType};
use App\Services\RequestService;

it('creates a request batch with payment', function () {
    $student = User::factory()->student()->create();
    $docs = DocumentType::factory()->count(2)->create(['fee' => 100]);

    $service = app(RequestService::class);
    $result = $service->createRequestBatch($student, $docs->pluck('id')->toArray(), 'For internship');

    expect($result['requests'])->toHaveCount(2);
    expect($result['payment']->total_amount)->toBe('200.00');
    expect($result['payment']->status)->toBe('pending');
});

it('rejects creating a batch when student has a pending request', function () {
    $student = User::factory()->student()->create();
    DocumentRequest::factory()->for($student)->pending()->create();
    $docs = DocumentType::factory()->count(1)->create();

    $service = app(RequestService::class);

    expect(fn () => $service->createRequestBatch($student, $docs->pluck('id')->toArray()))
        ->toThrow(\App\Exceptions\PendingRequestExistsException::class);
});
```

## Example: Feature Test (HTTP)

```php
// tests/Feature/Public/PublicRequestSubmissionTest.php
use App\Models\{User, DocumentType};

it('allows a public requestor to submit a request with receipt', function () {
    $doc = DocumentType::factory()->create(['fee' => 100]);

    $response = $this->post('/request-document', [
            'requester_name' => 'Juan Dela Cruz',
            'requester_email' => 'juan@example.test',
            'requester_contact_number' => '09171234567',
            'requester_student_id' => 'SVCI-2026-001',
            'requester_course' => 'BSIT',
            'requester_year_level' => 4,
            'items' => [['document_type_id' => $doc->id, 'copies' => 1]],
            'purpose' => 'For employment',
            'payment_method' => 'GCash',
            'payment_reference_number' => '123456',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('document_requests', [
        'user_id' => null,
        'document_type_id' => $doc->id,
        'status' => 'pending',
        'requester_student_id' => 'SVCI-2026-001',
    ]);
});

it('rejects public request submission without a receipt', function () {
    $doc = DocumentType::factory()->create();

    $response = $this->post('/request-document', [
        'requester_name' => 'Juan Dela Cruz',
        'requester_contact_number' => '09171234567',
        'requester_student_id' => 'SVCI-2026-001',
        'requester_course' => 'BSIT',
        'requester_year_level' => 4,
        'items' => [['document_type_id' => $doc->id, 'copies' => 1]],
        'purpose' => 'For employment',
        'payment_method' => 'GCash',
    ]);

    $response->assertSessionHasErrors('receipt');
});
```

## Example: Policy Test

```php
// tests/Unit/Policies/ClearancePolicyTest.php
use App\Models\{User, Clearance};
use App\Policies\ClearancePolicy;

it('allows teacher to sign teacher column only', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $clearance = Clearance::factory()->create();
    $policy = new ClearancePolicy();

    expect($policy->signFor($teacher, $clearance, 'teacher'))->toBeTrue();
    expect($policy->signFor($teacher, $clearance, 'dean'))->toBeFalse();
});
```

## Example: Browser Test (Playwright)

```ts
// tests/Browser/student-flow.spec.ts
import { test, expect } from '@playwright/test';

test('student can submit a request and upload payment', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name=email]', 'student@test.local');
    await page.fill('input[name=password]', 'password');
    await page.click('button[type=submit]');

    await expect(page).toHaveURL('/student/dashboard');

    await page.click('text=New Request');
    await page.check('text=Transcript of Records');
    await page.fill('textarea[name=purpose]', 'For employment');
    await page.click('text=Submit Request');

    await expect(page.locator('.toast')).toContainText('Request submitted');
});
```

## Coverage Targets

| Layer | Target |
|-------|--------|
| Services | 90% |
| Policies | 100% |
| Controllers | 80% |
| Models (custom methods) | 80% |
| Vue components | not enforced (covered by browser tests) |

Generate coverage report:

```bash
composer test:coverage
```

Coverage enforcement requires Xdebug or PCOV locally. CI installs Xdebug via `shivammathur/setup-php` and fails the PHP test job when coverage is below 80%. The current CI pipeline enforces coverage as pass/fail; it does not publish a coverage artifact.

## Notification & Broadcasting Tests

Use Laravel's testing helpers:

```php
use Illuminate\Support\Facades\{Notification, Event};

it('notifies admins when a request is submitted', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    // ... submit request
    Notification::assertSentTo($admin, RequestSubmittedNotification::class);
});

it('broadcasts when a payment is approved', function () {
    Event::fake();
    // ... approve payment
    Event::assertDispatched(PaymentApproved::class);
});
```

## File Upload Tests

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('accepts valid public request receipt upload', function () {
    Storage::fake('local');
    // ... act
    $response = $this->post(...);
    Storage::disk('local')->assertExists('payment-receipts/...');
});

it('rejects executable file uploads', function () {
    $response = $this->post('/request-document', [
        'receipt' => UploadedFile::fake()->create('virus.exe', 100),
    ]);
    $response->assertSessionHasErrors('receipt');
});
```

## CI Pipeline

Current CI is split into three jobs in `.github/workflows/ci.yml`:

| Job | Main commands | Notes |
|---|---|---|
| PHP Tests + Static Analysis | `npm run build`, `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse --no-progress`, `composer test:coverage` | Uses MySQL and Xdebug coverage; coverage is enforced at 80% by the Composer script. |
| Frontend Lint | `npm run lint` | Runs ESLint against `resources/js`. |
| Playwright E2E | `npx playwright install --with-deps chromium`, `npm run test:e2e` | Chromium-only, single worker, seeded with `E2E_REFRESH_DB=1`; uploads Playwright reports and test results only on failure. |

Run the same checks locally with:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
E2E_REFRESH_DB=1 npm run test:e2e
```

## TDD Workflow

For every feature in the implementation plans:

1. **RED** — write a failing test for the smallest behavior.
2. **GREEN** — implement just enough to pass.
3. **REFACTOR** — clean up without changing behavior.
4. **COMMIT** — small, focused commits.
5. **PR** — peer review before merge.
