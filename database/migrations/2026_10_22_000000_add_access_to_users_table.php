<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Members as users of the admin with limited access: administrators (everything, and managing the users) or
     * permissions by module (see App\Support\Permissions); signing in with the email or the mobile number; a
     * temporary password (sent by WhatsApp, valid for a while) to change on the first sign-in. The users there
     * already are administrators.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone_number', 20)->nullable()->unique()->after('email');
            $table->foreignId('member_id')->nullable()->after('phone_number')->constrained('members')->nullOnDelete();
            $table->boolean('is_admin')->default(false)->after('member_id');
            $table->json('permissions')->nullable()->after('is_admin');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('temporary_password_expires_at')->nullable()->after('must_change_password');
        });

        DB::table('users')->update(['is_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
            $table->dropUnique(['phone_number']);
            $table->dropColumn(['phone_number', 'is_admin', 'permissions', 'must_change_password', 'temporary_password_expires_at']);
        });
    }
};
