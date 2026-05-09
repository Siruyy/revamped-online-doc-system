<?php

/*
|--------------------------------------------------------------------------
| SVCI Registrar Policy Matrix
|--------------------------------------------------------------------------
|
| Mirrors docs/16-policy-matrix.md. This is the single source of truth
| that drives DocumentTypeSeeder, RequestRulesEngine, the student
| request wizard, and policy feature tests. When policy changes,
| update the Markdown spec, then update this file, then re-seed.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Fee Formulas
    |--------------------------------------------------------------------------
    |
    | Supported keys:
    |   - flat            => fee as-is
    |   - per_page        => fee * pages (default 1 page)
    |   - per_set         => fee * sets (TOR authentication)
    |   - per_5_copies    => fee * ceil(copies / 5)
    |
    */
    'fee_formulas' => ['flat', 'per_page', 'per_set', 'per_5_copies'],

    /*
    |--------------------------------------------------------------------------
    | Office / Clearance Role Directory
    |--------------------------------------------------------------------------
    |
    | System roles stay as-is (teacher, dean, accounting, sao) but the policy
    | also references president, alumni, guidance, library, registrar. The
    | clearance engine collapses unknown offices into the closest signing role
    | for now and exposes "virtual" offices for UI checklists.
    |
    */
    'offices' => [
        'president' => ['label' => 'Office of the School President', 'signer_role' => 'admin'],
        'dean' => ['label' => 'Office of the College Dean',      'signer_role' => 'dean'],
        'alumni' => ['label' => 'Alumni Office',                    'signer_role' => 'admin'],
        'guidance' => ['label' => 'Guidance Office',                  'signer_role' => 'sao'],
        'sao' => ['label' => 'Student Affairs Office',           'signer_role' => 'sao'],
        'library' => ['label' => 'Library',                          'signer_role' => 'admin'],
        'registrar' => ['label' => 'Office of the Registrar',         'signer_role' => 'admin'],
        'accounting' => ['label' => 'Accounting / Cashier',             'signer_role' => 'accounting'],
        'teacher' => ['label' => 'Subject Teacher / Adviser',        'signer_role' => 'teacher'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Release Channels
    |--------------------------------------------------------------------------
    */
    'release_channels' => [
        'registrar_window_1' => 'Window 1 – CAV Releasing',
        'registrar_window_4' => 'Window 4 – Authentication Desk',
        'registrar_window_5' => 'Window 5 – Records Intake (College)',
        'registrar_window_6' => 'Window 6 – Form 137-A Desk',
        'registrar_window_9' => 'Window 9 – Official Releasing',
        'annex_principal' => 'SVCI Annex – Office of the Principal',
        'email' => 'Email (registrarsoffice@gmail.com)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy Flags
    |--------------------------------------------------------------------------
    |
    | Flags are referenced by the engine and wizard to branch behavior:
    |   - requires_hd_return        : TOR-for-transfer, wait for HD lower portion
    |   - eligibility_special_class : gate certificate of special class
    |   - email_intake              : request letter via email (§11)
    |   - face_to_face_only         : enrollment survey (§10)
    |   - no_clearance_needed       : skip department clearance stages
    |   - transfer_exception        : §16 exception; requires CNO + notice
    |   - graduate_only             : CAR
    |   - nstp_only                 : cert_serial
    |   - basic_ed                  : BasicEd campus release (Annex)
    |   - cav_flow                  : CAV workflow variant
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Document Type Catalog
    |--------------------------------------------------------------------------
    */
    'document_types' => [

        // Academic (College / Graduate)
        'tor' => [
            'name' => 'Transcript of Records',
            'category' => 'Academic',
            'fee' => 140.00,
            'fee_formula' => 'per_page',
            'sla_days' => 14,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
            'requirements' => ['valid_id_photocopy_claimant'],
            'flags' => [],
        ],

        'tor_transfer' => [
            'name' => 'Transcript of Records (valid for Transfer)',
            'category' => 'Academic',
            'fee' => 140.00,
            'fee_formula' => 'per_page',
            'sla_days' => 14,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
            'requirements' => ['valid_id_photocopy_claimant'],
            'flags' => ['requires_hd_return'],
        ],

        'diploma' => [
            'name' => 'Diploma',
            'category' => 'Academic',
            'fee' => 15.00,
            'fee_formula' => 'per_5_copies',
            'sla_days' => 5,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
            'requirements' => ['valid_id_photocopy_claimant'],
            'flags' => [],
        ],

        'diploma_reissue_college' => [
            'name' => 'Diploma Re-issuance (College / Graduate)',
            'category' => 'Academic',
            'fee' => 310.00,
            'fee_formula' => 'flat',
            'sla_days' => 5,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
            'requirements' => ['affidavit_of_loss', 'valid_id_photocopy_claimant'],
            'flags' => [],
        ],

        'diploma_reissue_basic' => [
            'name' => 'Diploma Re-issuance (Basic Education)',
            'category' => 'BasicEd',
            'fee' => 310.00,
            'fee_formula' => 'flat',
            'sla_days' => 5,
            'submission_window' => 'annex_principal',
            'release_channel' => 'annex_principal',
            'offices' => ['registrar', 'accounting'],
            'requirements' => ['affidavit_of_loss', 'valid_id_photocopy_claimant'],
            'flags' => ['basic_ed'],
        ],

        'special_order' => [
            'name' => 'Special Order',
            'category' => 'Academic',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 5,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        // Certifications
        'cert_transfer_credential' => [
            'name' => 'Certificate of Transfer Credential (Honorable Dismissal)',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
            'requirements' => ['valid_id_photocopy_claimant'],
            'flags' => [],
        ],

        'cert_graduation' => [
            'name' => 'Certificate of Graduation',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_enrollment' => [
            'name' => 'Certificate of Enrollment',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_units_earned' => [
            'name' => 'Certificate of Units Earned',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_gwa' => [
            'name' => 'Certificate of General Weighted Average (GWA)',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_car' => [
            'name' => 'Certificate of Complete Academic Requirements (CAR)',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => ['graduate_only'],
        ],

        'cert_medium' => [
            'name' => 'Certificate of Medium of Instruction',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_grades' => [
            'name' => 'Certificate of Grades',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['teacher', 'dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_serial' => [
            'name' => 'Certificate of Serial Number (NSTP)',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['registrar', 'accounting'],
            'requirements' => [],
            'flags' => ['nstp_only'],
        ],

        'cert_special_class' => [
            'name' => 'Certificate of Special Class',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => ['eligibility_special_class'],
        ],

        'cert_permit_cross_enroll' => [
            'name' => 'Certificate of Permit to Cross-Enroll',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['dean', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_no_objection' => [
            'name' => 'Certificate of No Objection (CNO)',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_no_scholarship' => [
            'name' => 'Certificate of No Scholarship',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['sao', 'registrar', 'accounting'],
            'requirements' => [],
            'flags' => [],
        ],

        'cert_appearance' => [
            'name' => 'Certificate of Appearance',
            'category' => 'Certification',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_9',
            'offices' => ['registrar'],
            'requirements' => [],
            'flags' => [],
        ],

        // BasicEd
        'form_137a_personal' => [
            'name' => 'Form 137-A (Personal Copy)',
            'category' => 'BasicEd',
            'fee' => 130.00,
            'fee_formula' => 'flat',
            'sla_days' => 5,
            'submission_window' => 'registrar_window_6',
            'release_channel' => 'registrar_window_6',
            'offices' => ['registrar', 'accounting'],
            'requirements' => ['valid_id_photocopy_claimant'],
            'flags' => ['basic_ed'],
        ],

        'form_137a_transfer' => [
            'name' => 'Form 137-A (Valid for Transfer)',
            'category' => 'BasicEd',
            'fee' => 130.00,
            'fee_formula' => 'flat',
            'sla_days' => 5,
            'submission_window' => 'registrar_window_6',
            'release_channel' => 'registrar_window_6',
            'offices' => ['registrar', 'accounting'],
            'requirements' => ['official_request_letter', 'valid_id_photocopy_claimant'],
            'flags' => ['basic_ed'],
        ],

        'cert_basic_ed' => [
            'name' => 'Certification (Basic Education)',
            'category' => 'BasicEd',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'annex_principal',
            'release_channel' => 'annex_principal',
            'offices' => ['registrar', 'accounting'],
            'requirements' => [],
            'flags' => ['basic_ed'],
        ],

        'form_138_reissue' => [
            'name' => 'Form 138 Re-issuance (Report Card)',
            'category' => 'BasicEd',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 5,
            'submission_window' => 'annex_principal',
            'release_channel' => 'annex_principal',
            'offices' => ['registrar', 'accounting'],
            'requirements' => ['affidavit_of_loss'],
            'flags' => ['basic_ed'],
        ],

        // Special
        'enrollment_survey' => [
            'name' => 'Enrollment Population Survey',
            'category' => 'Special',
            'fee' => 0.00,
            'fee_formula' => 'flat',
            'sla_days' => 2,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_5',
            'offices' => [],
            'requirements' => ['official_request_letter'],
            'flags' => ['face_to_face_only', 'no_clearance_needed'],
        ],

        'records_verification' => [
            'name' => 'Student Records Verification',
            'category' => 'Special',
            'fee' => 100.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'email',
            'release_channel' => 'email',
            'offices' => [],
            'requirements' => ['request_letter', 'authorization_letter'],
            'flags' => ['email_intake', 'no_clearance_needed'],
        ],

        'cav' => [
            'name' => 'CAV (Certification, Authentication, Verification)',
            'category' => 'Special',
            'fee' => 140.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_1',
            'release_channel' => 'registrar_window_1',
            'offices' => ['registrar', 'accounting'],
            'requirements' => ['authenticated_tor'],
            'flags' => ['cav_flow'],
        ],

        'authentication' => [
            'name' => 'Authentication of School Records',
            'category' => 'Special',
            'fee' => 15.00,
            'fee_formula' => 'per_set',
            'sla_days' => 2,
            'submission_window' => 'registrar_window_4',
            'release_channel' => 'registrar_window_4',
            'offices' => ['registrar'],
            'requirements' => ['photocopy_records'],
            'flags' => ['no_clearance_needed'],
        ],

        'statement_of_account' => [
            'name' => 'Statement of Account',
            'category' => 'Special',
            'fee' => 65.00,
            'fee_formula' => 'flat',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_5',
            'offices' => ['accounting'],
            'requirements' => [],
            'flags' => ['no_clearance_needed'],
        ],

        'grades_printout' => [
            'name' => 'Grades Print-Out',
            'category' => 'Special',
            'fee' => 15.00,
            'fee_formula' => 'per_page',
            'sla_days' => 3,
            'submission_window' => 'registrar_window_5',
            'release_channel' => 'registrar_window_5',
            'offices' => ['registrar'],
            'requirements' => [],
            'flags' => ['no_clearance_needed'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Requirement Catalog
    |--------------------------------------------------------------------------
    */
    'requirements' => [
        'affidavit_of_loss' => [
            'label' => 'Notarized Affidavit of Loss',
            'hint' => 'From a lawyer. PDF or clear image.',
            'accept' => 'application/pdf,image/*',
        ],
        'official_request_letter' => [
            'label' => 'Official Request Letter',
            'hint' => 'Bearing the original signature of the School Principal or Registrar.',
            'accept' => 'application/pdf,image/*',
        ],
        'authenticated_tor' => [
            'label' => 'Authenticated Transcript of Records',
            'hint' => 'Previously authenticated copy (Window 4).',
            'accept' => 'application/pdf,image/*',
        ],
        'authenticated_diploma' => [
            'label' => 'Authenticated Diploma',
            'hint' => 'Required for graduates applying for CAV.',
            'accept' => 'application/pdf,image/*',
        ],
        'authenticated_special_order' => [
            'label' => 'Authenticated Special Order',
            'hint' => 'Required for graduates applying for CAV.',
            'accept' => 'application/pdf,image/*',
        ],
        'authenticated_cert_units_earned' => [
            'label' => 'Authenticated Certificate of Units Earned',
            'hint' => 'Required for non-graduates applying for CAV.',
            'accept' => 'application/pdf,image/*',
        ],
        'photocopy_records' => [
            'label' => 'Photocopy of Records to Authenticate',
            'hint' => 'Clear photocopy for the dry-seal stamp.',
            'accept' => 'application/pdf,image/*',
        ],
        'request_letter' => [
            'label' => 'Verification Request Letter',
            'hint' => 'Must include student name, course, year, purpose, and authorizing party (per §11.1).',
            'accept' => 'application/pdf,image/*',
        ],
        'authorization_letter' => [
            'label' => 'Authorization Letter',
            'hint' => 'From the student/alumnus permitting verification or release.',
            'accept' => 'application/pdf,image/*',
        ],
        'spa' => [
            'label' => 'Special Power of Attorney',
            'hint' => 'Required if claimant is not the owner and owner resides in PH.',
            'accept' => 'application/pdf,image/*',
        ],
        'valid_id_photocopy_owner' => [
            'label' => 'Valid ID Photocopy (Record Owner)',
            'hint' => 'Required for proxy release.',
            'accept' => 'application/pdf,image/*',
        ],
        'valid_id_photocopy_claimant' => [
            'label' => 'Valid ID Photocopy (Claimant)',
            'hint' => 'Required at the releasing window.',
            'accept' => 'application/pdf,image/*',
        ],
        'death_certificate' => [
            'label' => 'Death Certificate',
            'hint' => 'For deceased student record requisition.',
            'accept' => 'application/pdf,image/*',
        ],
        'notarized_affidavit_deceased' => [
            'label' => 'Notarized Affidavit (Relationship + Purpose)',
            'hint' => 'Required for deceased record requisition.',
            'accept' => 'application/pdf,image/*',
        ],
        'certificate_of_no_objection' => [
            'label' => 'Certificate of No Objection (CNO)',
            'hint' => 'From the receiving institution per §16.7–16.8.',
            'accept' => 'application/pdf,image/*',
        ],
        'official_external_notice' => [
            'label' => 'Official Notice / Written Request',
            'hint' => 'From the government agency, employer, or authority (§16.6.2).',
            'accept' => 'application/pdf,image/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA Calendar
    |--------------------------------------------------------------------------
    */
    'sla' => [
        'working_days_only' => true,
        'exclude_weekends' => true,
        'holidays' => [
            // Add Philippine holidays here; left empty by default.
        ],
        'pause_reasons' => [
            'enrollment_period' => 'Ongoing enrollment period',
            'graduation_week' => 'Week leading to graduation',
            'board_exams' => 'Board examination schedule',
            'records_backlog' => 'Records backlog / high volume',
            'external_dependency' => 'Awaiting external document',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Claim Slip
    |--------------------------------------------------------------------------
    */
    'claim_slip' => [
        'states' => ['pending', 'ready', 'released', 'expired', 'void'],
        'grace_period_days' => 30,
    ],
];
