# 11 — File Storage

## Storage Disks

```php
// config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root'   => storage_path('app/public'),
        'url'    => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    'local' => [
        'driver' => 'local',
        'root'   => storage_path('app/private'),
        'visibility' => 'private',
    ],
],
```

| Disk | Purpose | Access |
|------|---------|--------|
| `public` | Avatars only | Direct URL via `storage` symlink |
| `local` | Payment receipts, request requirements, signatures, clearance files, generated PDFs | Authorized controller route |

## Folder Structure

```
storage/app/
├── public/
│   └── avatars/
│       └── {userId}/
│           └── {uuid}.{ext}
└── private/
    ├── payment-receipts/
    │   ├── {userId}/
    │   └── public/{requestId}/
    │       └── {uuid}.{ext}
    ├── request-requirements/
    │   ├── {userId}/{requestId}/
    │   └── public/{requestId}/
    │       └── {uuid}.{ext}
    ├── signatures/
    │   └── {userId}/
    │       └── {uuid}.{ext}
    ├── clearance-files/
    │   └── {userId}/
    │       └── {uuid}.{ext}
    └── pdfs/
        └── clearance/
            └── {clearanceId}.pdf
```

## Upload Validation Rules

| File type | Allowed extensions | MIME types | Max size |
|-----------|-------------------|------------|----------|
| Avatar | `jpg, jpeg, png, webp` | `image/jpeg, image/png, image/webp` | 2 MB |
| Payment receipt | `jpg, jpeg, png, pdf` | `image/jpeg, image/png, application/pdf` | 5 MB |
| Request requirement | `jpg, jpeg, png, pdf` | `image/jpeg, image/png, application/pdf` | 5 MB |
| Signature | `png` (transparent) | `image/png` | 1 MB |
| Clearance file | `jpg, jpeg, png, pdf` | (same) | 5 MB |

Validation example:

```php
// In a Form Request
'receipt' => [
    'required',
    'file',
    'mimes:jpg,jpeg,png,pdf',
    'mimetypes:image/jpeg,image/png,application/pdf',
    'max:5120',
],
```

Both `mimes` and `mimetypes` are checked — `mimes` validates extension, `mimetypes` validates the actual file content (prevents disguised files).

## File Naming Strategy

- **Never use the original filename** — possible XSS, path traversal, collision.
- Generate **UUIDv4** filename, preserve only the validated extension.
- Store the original filename in DB as a separate column (`original_filename`) if needed for display.

```php
$file = $request->file('receipt');
$ext = $file->getClientOriginalExtension();
$filename = Str::uuid().'.'.$ext;
$path = $file->storeAs(
    "payment-receipts/{$user->id}",
    $filename,
    'local'
);
$payment->update(['receipt_path' => $path]);
```

## Serving Private Files

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/files/payment-receipt/{payment}', [FileController::class, 'paymentReceipt'])
        ->name('files.payment-receipt');
    Route::get('/files/request-requirements/{requirement}', [FileController::class, 'requestRequirement'])
        ->name('files.request-requirement');
    Route::get('/files/clearance/{clearance}/pdf', [FileController::class, 'clearancePdf'])
        ->name('files.clearance-pdf');
});

// Controller
public function paymentReceipt(Payment $payment): StreamedResponse
{
    $this->authorize('view', $payment);

    return Storage::disk('local')->response(
        $payment->receipt_path,
        $payment->original_filename ?? 'receipt.'.pathinfo($payment->receipt_path, PATHINFO_EXTENSION)
    );
}
```

Do not link request requirement uploads as `/storage/{file_path}`. Requirement files live on the private `local` disk and must be served through `files.request-requirement` after policy checks. This fixes the known 404 from `/storage/request-requirements/...`.

## Public File Serving (Avatars)

Avatars are public for performance. Served via `storage:link`:

```bash
php artisan storage:link
```

Frontend uses:

```vue
<img :src="`/storage/${user.avatar_path}`" />
```

## Image Processing

For avatars, resize on upload to standard dimensions:

```php
use Intervention\Image\Laravel\Facades\Image;

$image = Image::read($file)
    ->cover(256, 256)  // square crop
    ->toWebp(85);

Storage::disk('public')->put("avatars/{$user->id}/{$filename}.webp", $image);
```

This requires `intervention/image` v3.

## File Cleanup

- When a user uploads a new avatar, delete the old one.
- When a payment is denied and re-uploaded, keep the old receipt for audit (don't delete).
- When a user is deleted (soft-delete), keep files for 30 days then purge via scheduled job.

```php
// app/Console/Commands/PurgeDeletedUserFiles.php
// Scheduled daily
foreach (User::onlyTrashed()->where('deleted_at', '<', now()->subDays(30))->get() as $user) {
    Storage::disk('local')->deleteDirectory("payment-receipts/{$user->id}");
    Storage::disk('local')->deleteDirectory("signatures/{$user->id}");
    Storage::disk('local')->deleteDirectory("clearance-files/{$user->id}");
    Storage::disk('public')->deleteDirectory("avatars/{$user->id}");
    $user->forceDelete();
}
```

## Storage Permissions (Production)

```bash
chown -R www-data:www-data storage/
chmod -R 755 storage/
```

In Docker, the `storage/` directory is mounted as a **named volume** so it persists across container restarts.

## Backup Strategy

- **Daily DB dump** to a separate location (DigitalOcean Spaces or external server).
- **Weekly storage tarball** of `storage/app/` to the same backup location.
- Retention: 30 days daily, 12 weeks weekly.

```bash
# Cron job (in Dokploy or system crontab)
0 2 * * * mysqldump --single-transaction $DB_NAME | gzip > /backups/db-$(date +\%F).sql.gz
0 3 * * 0 tar czf /backups/storage-$(date +\%F).tar.gz /var/www/html/storage/app/
```

## File Upload UX (Vue)

- Drag-and-drop zone with file-picker fallback.
- Show preview thumbnail for images.
- Show filename + size for PDFs.
- Display upload progress bar (Inertia's `useForm` provides `progress`).
- Disable submit button while uploading.
- Clear error messages on validation failure.

```vue
<FileUpload
    v-model="form.receipt"
    :error="form.errors.receipt"
    accept="image/jpeg,image/png,application/pdf"
    :max-size-mb="5"
/>
```
