<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePaymentProfileRequest;
use App\Models\PaymentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentProfileController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PaymentProfile::class);

        $profiles = PaymentProfile::query()
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (PaymentProfile $p) => $this->serializeProfile($p));

        return Inertia::render('Admin/Settings/PaymentProfile', [
            'profiles' => $profiles,
        ]);
    }

    public function store(SavePaymentProfileRequest $request): RedirectResponse
    {
        $this->authorize('manage', PaymentProfile::class);

        $validated = $request->validated();

        $profile = new PaymentProfile([
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if ($request->hasFile('qr_image')) {
            $profile->qr_path = $this->storeQrFile($request);
        }

        $profile->save();

        return back()->with('status', 'Payment profile created.');
    }

    public function update(SavePaymentProfileRequest $request, PaymentProfile $paymentProfile): RedirectResponse
    {
        $this->authorize('manage', PaymentProfile::class);

        $validated = $request->validated();

        $paymentProfile->fill([
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => $validated['is_active'] ?? $paymentProfile->is_active,
        ]);

        $oldPath = $paymentProfile->qr_path;

        if ($request->hasFile('qr_image')) {
            $paymentProfile->qr_path = $this->storeQrFile($request);
        }

        $paymentProfile->save();

        if ($oldPath !== $paymentProfile->qr_path) {
            $this->deleteQrFile($oldPath);
        }

        return back()->with('status', 'Payment profile updated.');
    }

    public function toggle(PaymentProfile $paymentProfile): RedirectResponse
    {
        $this->authorize('manage', PaymentProfile::class);

        $paymentProfile->update(['is_active' => ! $paymentProfile->is_active]);

        return back()->with('status', $paymentProfile->is_active ? 'Profile activated.' : 'Profile deactivated.');
    }

    public function destroy(PaymentProfile $paymentProfile): RedirectResponse
    {
        $this->authorize('manage', PaymentProfile::class);

        $this->deleteQrFile($paymentProfile->qr_path);
        $paymentProfile->delete();

        return back()->with('status', 'Payment profile deleted.');
    }

    public function removeQr(PaymentProfile $paymentProfile): RedirectResponse
    {
        $this->authorize('manage', PaymentProfile::class);

        $this->deleteQrFile($paymentProfile->qr_path);
        $paymentProfile->update(['qr_path' => null]);

        return back()->with('status', 'QR code removed.');
    }

    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(PaymentProfile $p): array
    {
        return [
            'id' => $p->id,
            'bank_name' => $p->bank_name,
            'account_name' => $p->account_name,
            'account_number' => $p->account_number,
            'instructions' => $p->instructions,
            'is_active' => $p->is_active,
            'qr_url' => $p->qr_path ? route('files.payment-qr', $p->id) : null,
        ];
    }

    private function storeQrFile(Request $request): string
    {
        $file = $request->file('qr_image');
        $ext = strtolower($file->getClientOriginalExtension());
        $path = 'payment-qr/'.Str::uuid().'.'.$ext;
        Storage::disk('local')->put($path, $file->getContent());

        return $path;
    }

    private function deleteQrFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
