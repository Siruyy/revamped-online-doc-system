<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Claim Slip {{ $slip->claim_number }}</title>
    <style>
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
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
        <h1>ST. VINCENT'S COLLEGE INCORPORATED</h1>
        <p class="muted">Office of the Registrar · Padre Ramon Street, Estaka, Dipolog City</p>
        <h2 style="margin-top: 16px;">DOCUMENT CLAIM SLIP</h2>
    </div>
    <div class="slip">
        <p class="center muted">Claim number</p>
        <p class="center number">{{ $slip->claim_number }}</p>
        <table>
            <tr><th>Request reference</th><td>{{ $request->reference_no }}</td></tr>
            <tr><th>Requestor</th><td>{{ $request->requester_name ?? $request->user?->fullname }}</td></tr>
            <tr><th>Documents</th><td>{{ $request->items->map(fn ($item) => $item->documentType?->name.' × '.$item->copies)->join(', ') }}</td></tr>
            <tr><th>Release date</th><td>{{ $slip->claim_date?->format('F j, Y') ?? 'Coordinate with the Registrar' }}</td></tr>
            <tr><th>Release channel</th><td>{{ str_replace('_', ' ', $slip->release_channel) }}</td></tr>
        </table>
        <div class="notice"><strong>Bring one valid ID.</strong> A representative must also bring the required authorization or Special Power of Attorney.</div>
    </div>
    <p class="center muted" style="margin-top: 18px;">Generated {{ $generatedAt->format('F j, Y g:i A') }} · registrarsoffice@svc.edu.ph · 09515388282</p>
</body>
</html>
