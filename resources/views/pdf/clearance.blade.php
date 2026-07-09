<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Clearance Certificate</title>
    <style>
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0 0 4px; text-align: center; }
        h2 { font-size: 14px; margin: 22px 0 8px; }
        .muted { color: #475569; }
        .center { text-align: center; }
        .panel { border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 18px; padding: 14px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        .status { font-weight: bold; text-transform: uppercase; }
        .footer { bottom: 24px; color: #64748b; font-size: 10px; position: fixed; text-align: center; width: 100%; }
    </style>
</head>
<body>
    @php($signatories = $signatories ?? [])
    @php($request = $clearance->documentRequest)
    @php($studentName = $clearance->user?->fullname ?? $request?->requester_name ?? 'N/A')
    @php($studentId = $clearance->user?->student_id ?? $request?->requester_student_id ?? 'N/A')
    @php($course = $clearance->user?->course ?? $request?->requester_course ?? 'N/A')
    @php($yearLevel = $clearance->user?->year_level ?? $request?->requester_year_level ?? 'N/A')

    <h1>Saint Vincent's College Incorporated</h1>
    <p class="center muted">Online Document Request and Management System</p>
    <h2 class="center">Department Clearance Certificate</h2>

    <div class="panel">
        <table>
            <tr>
                <th>Student</th>
                <td>{{ $studentName }}</td>
                <th>Student ID</th>
                <td>{{ $studentId }}</td>
            </tr>
            <tr>
                <th>Course / Year</th>
                <td>{{ $course }} / {{ $yearLevel }}</td>
                <th>Request Ref</th>
                <td>{{ $request->reference_no ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td class="status">{{ str_replace('_', ' ', $clearance->overall_status) }}</td>
                <th>Completed</th>
                <td>{{ $clearance->completed_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <h2>Department Sign-Offs</h2>
    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Status</th>
                <th>Signed By</th>
                <th>Date Signed</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($signatories as $signatory)
                @php($signedAt = $clearance->{$signatory['signed_at']})
                <tr>
                    <td>{{ $signatory['label'] }}</td>
                    <td>{{ ucfirst($clearance->{$signatory['status']} ?? 'pending') }}</td>
                    <td>{{ $clearance->{$signatory['signer']}?->fullname ?? 'N/A' }}</td>
                    <td>{{ $signedAt?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                    <td>{{ $clearance->{$signatory['remarks']} ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted">Generated at {{ $generatedAt->format('M d, Y h:i A') }}. This certificate is valid only when downloaded from the authorized portal.</p>
    <div class="footer">SVCI Online Document Request and Management System</div>
</body>
</html>
