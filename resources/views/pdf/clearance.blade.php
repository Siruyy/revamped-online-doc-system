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
    <h1>Saint Vincent's College Incorporated</h1>
    <p class="center muted">Online Document Request and Management System</p>
    <h2 class="center">Department Clearance Certificate</h2>

    <div class="panel">
        <table>
            <tr>
                <th>Student</th>
                <td>{{ $clearance->user->fullname }}</td>
                <th>Student ID</th>
                <td>{{ $clearance->user->student_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Course / Year</th>
                <td>{{ $clearance->user->course ?? 'N/A' }} / {{ $clearance->user->year_level ?? 'N/A' }}</td>
                <th>Request Ref</th>
                <td>{{ $clearance->documentRequest->reference_no ?? 'N/A' }}</td>
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
                <th>Signed At</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Teacher</td>
                <td>{{ ucfirst($clearance->teacher_status) }}</td>
                <td>{{ $clearance->teacherSigner->fullname ?? 'N/A' }}</td>
                <td>{{ $clearance->teacher_signed_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                <td>{{ $clearance->teacher_remarks ?? '' }}</td>
            </tr>
            <tr>
                <td>Dean</td>
                <td>{{ ucfirst($clearance->dean_status) }}</td>
                <td>{{ $clearance->deanSigner->fullname ?? 'N/A' }}</td>
                <td>{{ $clearance->dean_signed_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                <td>{{ $clearance->dean_remarks ?? '' }}</td>
            </tr>
            <tr>
                <td>Accounting</td>
                <td>{{ ucfirst($clearance->accounting_status) }}</td>
                <td>{{ $clearance->accountingSigner->fullname ?? 'N/A' }}</td>
                <td>{{ $clearance->accounting_signed_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                <td>{{ $clearance->accounting_remarks ?? '' }}</td>
            </tr>
            <tr>
                <td>SAO</td>
                <td>{{ ucfirst($clearance->sao_status) }}</td>
                <td>{{ $clearance->saoSigner->fullname ?? 'N/A' }}</td>
                <td>{{ $clearance->sao_signed_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                <td>{{ $clearance->sao_remarks ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted">Generated at {{ $generatedAt->format('M d, Y h:i A') }}. This certificate is valid only when downloaded from the authorized portal.</p>
    <div class="footer">SVCI Online Document Request and Management System</div>
</body>
</html>
