<?php

namespace App\Services\Policy;

use App\Models\DocumentType;
use App\Models\User;

/**
 * Central policy gate for document requests. Reads from config/policy.php and
 * the DocumentType row, evaluates eligibility for the requesting user, and
 * exposes a declarative spec that the UI and the RequestService share.
 */
class RequestRulesEngine
{
    /**
     * Get the normalized policy rule spec for a document type.
     *
     * @return array{
     *   code:string, name:string, category:string, fee:float, fee_formula:string,
     *   sla_days:int, submission_window:?string, release_channel:?string,
     *   offices:array<int,string>, requirements:array<int,string>, flags:array<int,string>
     * }
     */
    public function rulesFor(DocumentType $type): array
    {
        $configured = config('policy.document_types.'.$type->code, []);

        return [
            'code' => $type->code,
            'name' => $type->name,
            'category' => $type->category ?? ($configured['category'] ?? 'Special'),
            'fee' => (float) $type->fee,
            'fee_formula' => $type->fee_formula ?: ($configured['fee_formula'] ?? 'flat'),
            'sla_days' => (int) ($type->processing_days ?: ($configured['sla_days'] ?? 3)),
            'submission_window' => $type->submission_window ?: ($configured['submission_window'] ?? null),
            'release_channel' => $type->release_channel ?: ($configured['release_channel'] ?? null),
            'offices' => (array) ($type->offices ?: ($configured['offices'] ?? [])),
            'requirements' => (array) ($type->requirements ?: ($configured['requirements'] ?? [])),
            'flags' => (array) ($type->flags ?: ($configured['flags'] ?? [])),
        ];
    }

    /**
     * Validate that the given user is eligible to request this document type
     * under current policy. Returns an array of human-readable errors. Empty
     * array => eligible.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function validateEligibility(User $user, DocumentType $type, array $context = []): array
    {
        $errors = [];
        $rules = $this->rulesFor($type);
        $flags = $rules['flags'];

        // §16: transferred/dismissed students cannot request records unless an
        // exception with CNO and external notice is attached.
        if ($user->isTransferred() && ! in_array('transfer_exception_flow', $flags, true)) {
            $exceptionApproved = $context['transfer_exception_approved'] ?? false;
            $hasCNO = $context['has_cno'] ?? false;
            $hasExternalNotice = $context['has_external_notice'] ?? false;

            if (! $exceptionApproved) {
                if (! $hasCNO || ! $hasExternalNotice) {
                    $errors[] = 'Records for transferred/dismissed students are not released without an approved exception. Please submit a Certificate of No Objection (CNO) from your receiving school and the official external notice (§16).';
                }
            }
        }

        if (in_array('graduate_only', $flags, true) && ! $user->is_graduate) {
            $errors[] = 'This certification is only issued to students who have graduated.';
        }

        if (in_array('nstp_only', $flags, true) && ! $user->is_nstp) {
            $errors[] = 'This certification is only available to NSTP graduates.';
        }

        if (in_array('eligibility_special_class', $flags, true)) {
            $checklist = $context['special_class_eligibility'] ?? [];
            $required = ['graduating_this_term', 'subject_deficiency_certified', 'subject_conflict'];
            $anyChecked = false;

            foreach ($required as $key) {
                if (! empty($checklist[$key])) {
                    $anyChecked = true;
                    break;
                }
            }

            if (! $anyChecked) {
                $errors[] = 'You must confirm at least one Special Class eligibility criterion (§12.1).';
            }
        }

        if (in_array('face_to_face_only', $flags, true)) {
            // Enrollment survey is face-to-face only; system creates a manual ticket.
            $errors[] = 'Enrollment Survey requests must be filed in person at the Office of the Registrar with an official request letter.';
        }

        return $errors;
    }

    /**
     * Compute total fee: fee_per_page × page_count × quantity (copies).
     *
     * @param  array<string, mixed>  $spec
     */
    public function computeFee(DocumentType $type, array $spec = []): float
    {
        $fee = (float) $type->fee;
        $pages = max(1, (int) ($spec['page_count'] ?? $type->default_page_count ?? 1));
        $quantity = max(1, (int) ($spec['quantity'] ?? 1));

        return round($fee * $pages * $quantity, 2);
    }

    /**
     * Offices required for clearance on this type, translated to signer roles
     * the current schema supports: teacher/dean/accounting/sao.
     *
     * Returns an empty array if clearance is not needed.
     *
     * @return array<int, string>
     */
    public function clearanceSignerRoles(DocumentType $type): array
    {
        if (! $type->requiresClearance()) {
            return [];
        }

        $offices = $this->rulesFor($type)['offices'];
        $signers = [];

        foreach ($offices as $office) {
            $role = config('policy.offices.'.$office.'.signer_role');

            if ($role && ! in_array($role, $signers, true)) {
                $signers[] = $role;
            }
        }

        // Filter to the roles the current clearance table actually supports.
        $supported = ['teacher', 'dean', 'accounting', 'sao'];

        return array_values(array_intersect($signers, $supported));
    }
}
