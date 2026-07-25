<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLES = [
        'student',
        'admin',
        'teacher',
        'dean',
        'principal',
        'accounting',
        'sao',
        'president',
        'librarian',
        'student_affairs',
        'alumni',
        'guidance',
        'superadmin',
    ];

    public function up(): void
    {
        $this->updateRoleEnum(self::ROLES);

        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('requester_division', 32)->default('college')->after('requester_student_id');
            $table->string('basic_education_level', 32)->nullable()->after('requester_division');
            $table->string('requester_last_term_attended', 32)->nullable()->after('requester_graduation_or_last_sem');
            $table->string('requester_last_year_attended', 20)->nullable()->after('requester_last_term_attended');

            $table->index(['requester_division', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropIndex(['requester_division', 'status']);
            $table->dropColumn([
                'requester_division',
                'basic_education_level',
                'requester_last_term_attended',
                'requester_last_year_attended',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'principal')->update(['role' => 'dean']);
        }

        $this->updateRoleEnum(array_values(array_diff(self::ROLES, ['principal'])));
    }

    /**
     * SQLite stores Laravel enums as text, so only MySQL needs an enum alteration.
     *
     * @param  list<string>  $roles
     */
    private function updateRoleEnum(array $roles): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $values = collect($roles)
            ->map(fn (string $role): string => DB::getPdo()->quote($role))
            ->implode(',');

        DB::statement("ALTER TABLE users MODIFY role ENUM({$values}) NOT NULL");
    }
};
