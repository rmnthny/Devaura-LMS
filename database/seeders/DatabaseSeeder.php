<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ReferenceDataSeeder::class,
        ]);

        if (! \Illuminate\Support\Facades\Schema::hasTable('department') || ! \Illuminate\Support\Facades\Schema::hasTable('library_member') || ! \Illuminate\Support\Facades\Schema::hasTable('library_personnel')) {
            return;
        }

        if (User::role(Rbac::SUPERADMIN)->exists()) {
            return;
        }

        $defaultDept = Department::where('department_name', 'English')->first();

        // The single Head Librarian account (a Faculty teacher with superadmin privilege).
        $headLibrarian = User::firstOrCreate(
            ['email' => 'devaurapuptaguigbsit@gmail.com'],
            [
                'username' => 'headlibrarian',
                'password' => 'password',
                'email_verified_at' => now(),
                'user_qr_value' => 'UBNHS-'.now()->year.'-0001',
            ],
        );

        $headLibrarian->syncRoles([Rbac::SUPERADMIN]);

        $headMember = LibraryMember::where('account_id', $headLibrarian->id)->first();
        if (! $headMember) {
            $code = LibraryMember::generateMemberCode();
            $headLibrarian->forceFill(['user_qr_value' => $code])->save();

            LibraryMember::create([
                'account_id' => $headLibrarian->id,
                'department_id' => $defaultDept?->department_id,
                'member_role' => LibraryMember::ROLE_FACULTY,
                'reference_number' => '1000001',
                'member_code' => $code,
                'last_name' => 'Librarian',
                'first_name' => 'Head',
                'middle_initial' => null,
            ]);
        }

        LibraryPersonnel::firstOrCreate(
            ['account_id' => $headLibrarian->id],
            [
                'department_id' => $defaultDept?->department_id,
                'last_name' => 'Librarian',
                'first_name' => 'Head',
                'middle_initial' => null,
            ],
        );
    }
}
