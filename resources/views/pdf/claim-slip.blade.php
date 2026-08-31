<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Claim Slip {{ $slip->claim_number }}</title>
    <style>
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
        .brand { color: #173b75; font-weight: bold; letter-spacing: .4px; }
        .logo { height: 58px; margin-bottom: 6px; }
        .muted { color: #475569; }
        .slip { border: 2px solid #173b75; margin-top: 28px; padding: 24px; }
        .number { color: #173b75; font-size: 22px; font-weight: bold; letter-spacing: 1px; margin: 14px 0; }
        table { border-collapse: collapse; margin-top: 18px; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; width: 32%; }
        .notice { background: #fff7ed; border: 1px solid #fed7aa; margin-top: 18px; padding: 12px; }
    </style>
</head>
<body>
    @php($request = $slip->documentRequest)
    <div class="center">
        @if (file_exists(public_path('images/svci-logo.svg')))
            <img class="logo" src="{{ public_path('images/svci-logo.svg') }}" alt="SVCI logo">
        @endif
        <h1>ST. VINCENT'S COLLEGE INCORPORATED</h1>
        <p class="brand">OFFICE OF THE REGISTRAR</p>
        <p class="muted">Padre Ramon Street, Estaka, Dipolog City 7100 Philippines</p>
        <h2 style="margin-top: 16px;">DOCUMENT CLAIM SLIP</h2>
    </div>
    <div class="slip">
        <p class="center muted">Claim number</p>
        <p class="center number">{{ $slip->claim_number }}</p>
        <table>
            <tr><th>Request reference</th><td>{{ $request->reference_no }}</td></tr>
            <tr><th>Document owner</th><td>{{ $request->requester_name ?? $request->user?->fullname }}</td></tr>
            <tr><th>Claimant / representative</th><td>{{ $request->requester_claimant_name ?: ($request->requester_name ?? $request->user?->fullname) }}</td></tr>
            @if ($request->is_proxy_request)
                <tr><th>Relationship</th><td>{{ $request->representative_relationship ?: 'Authorized representative' }}</td></tr>
                <tr><th>Owner residence</th><td>{{ $request->owner_residence === 'outside_country' ? 'Outside the Philippines' : 'Within the Philippines' }}</td></tr>
            @endif
            <tr><th>Documents</th><td>{{ $request->items->map(fn ($item) => $item->documentType?->name.' × '.$item->copies)->join(', ') }}</td></tr>
            <tr><th>Fulfillment</th><td>{{ ucfirst($request->fulfillment_method ?? 'pickup') }}</td></tr>
            @if ($request->fulfillment_method === 'delivery')
                <tr><th>Courier</th><td>{{ $request->courier_name ?: 'Courier delivery' }}{{ $request->courier_tracking_number ? ' · '.$request->courier_tracking_number : '' }}</td></tr>
            @endif
            <tr><th>Release date</th><td>{{ $slip->claim_date?->format('F j, Y') ?? 'Coordinate with the Registrar' }}</td></tr>
            <tr><th>Release channel</th><td>{{ config('policy.release_channels.'.$slip->release_channel, str_replace('_', ' ', $slip->release_channel)) }}</td></tr>
        </table>
        <div class="notice"><strong>Release checklist:</strong><br>• Bring the claim slip and one valid ID of the document owner.<br>• If claiming through a representative, bring the representative’s valid ID, the owner’s valid ID photocopy, and the required authorization letter or Special Power of Attorney.</div>
    </div>
    <p class="center muted" style="margin-top: 18px;">Generated {{ $generatedAt->format('F j, Y g:i A') }} · registrarsoffice@svc.edu.ph · 09515388282</p>
</body>
</html>
