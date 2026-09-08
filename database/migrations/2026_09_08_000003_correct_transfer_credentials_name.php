<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_types')->where('code', 'tor_transfer')
            ->whereIn('name', ['Transcript of Records (valid for Transfer)', 'Transcript of Records (Valid for Transfer)', 'TOR valid for transfer'])
            ->update(['name' => 'Transfer Credentials']);
    }

    public function down(): void
    {
        // Intentionally preserve the corrected name; the original spelling cannot be inferred safely.
    }
};
