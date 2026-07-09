<?php

use App\Support\ClearanceSignatories;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->relaxUserRoleColumn();

        Schema::table('clearances', function (Blueprint $table) {
            foreach (array_diff(ClearanceSignatories::roles(), ['dean']) as $role) {
                $table->enum("{$role}_status", ['pending', 'cleared', 'denied'])->default('pending');
                $table->text("{$role}_remarks")->nullable();
                $table->foreignId("{$role}_signed_by")->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamp("{$role}_signed_at")->nullable();
            }
        });

        DB::table('clearances')
            ->where('overall_status', 'completed')
            ->update(collect(array_diff(ClearanceSignatories::roles(), ['dean']))
                ->mapWithKeys(fn (string $role): array => ["{$role}_status" => 'cleared'])
                ->all());
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            foreach (array_reverse(array_diff(ClearanceSignatories::roles(), ['dean'])) as $role) {
                $table->dropForeign(["{$role}_signed_by"]);
                $table->dropColumn([
                    "{$role}_status",
                    "{$role}_remarks",
                    "{$role}_signed_by",
                    "{$role}_signed_at",
                ]);
            }
        });
    }

    private function relaxUserRoleColumn(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY role VARCHAR(32) NOT NULL');
    }
};
