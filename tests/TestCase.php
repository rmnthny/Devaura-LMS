<?php

namespace Tests;

use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $database = $app['db']->connection();

        if ($database->getDriverName() !== 'mysql' || $database->getDatabaseName() !== 'devaura_lms_test') {
            throw new \RuntimeException('The feature suite must use the dedicated devaura_lms_test database.');
        }

        return $app;
    }

    protected function headLibrarian(): User
    {
        $head = User::factory()->faculty()->withRole(Rbac::SUPERADMIN)->create([
            'username' => 'headlibrarian',
            'password_hash' => 'password',
        ]);
        LibraryMember::factory()->forAccount($head)->create([
            'first_name' => 'Head', 'last_name' => 'Librarian', 'middle_initial' => null,
        ]);
        LibraryPersonnel::factory()->forAccount($head)->create([
            'first_name' => 'Head', 'last_name' => 'Librarian', 'middle_initial' => null,
        ]);

        return $head;
    }
}
