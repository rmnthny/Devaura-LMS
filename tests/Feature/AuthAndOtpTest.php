<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthAndOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_student_can_sign_up_verify_otp_and_receive_member_qr(): void
    {
        Mail::fake();
        Storage::fake('public');

        $response = $this->postJson(route('api.v1.register.store'), [
            'member_role' => 'Student',
            'reference_number' => '136812345678',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_initial' => 'M',
            'email' => 'juan@ubnhs.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSuccessful();

        $user = User::where('email', 'juan@ubnhs.test')->firstOrFail();
        $this->assertTrue($user->hasRole(Rbac::STUDENT));
        $this->assertNull($user->email_verified_at);

        $otpCode = null;
        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) use ($user, &$otpCode) {
            $otpCode = $mail->code;

            return $mail->hasTo($user->email) && strlen($mail->code) === 6;
        });

        // Verify the 6-digit OTP.
        $verifyResponse = $this->postJson(route('api.v1.otp.verify'), ['code' => $otpCode]);
        $verifyResponse->assertOk();
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $member = $user->member->fresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertMatchesRegularExpression('/^UBNHS-\d{4}-\d{4}$/', $member->member_code);
        $this->assertEquals($member->member_code, $user->user_qr_value);

        // Dashboard renders with the personal QR SVG and no-cache headers.
        $dash = $this->getJson(route('api.v1.me'));
        $dash->assertOk()->assertSee($member->member_code);
        $this->assertStringContainsString('no-store', $dash->headers->get('Cache-Control'));
    }

    public function test_faculty_sign_up_requires_and_saves_department(): void
    {
        Mail::fake();
        $dept = Department::factory()->create(['department_name' => 'Science']);

        // Missing department & letters in Faculty ID are rejected
        $this->postJson(route('api.v1.register.store'), [
            'member_role' => 'Faculty',
            'reference_number' => 'FAC-2026-01',
            'last_name' => 'Cruz',
            'first_name' => 'Elena',
            'email' => 'elena@ubnhs.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertJsonValidationErrors(['department_id', 'reference_number']);

        // With department & 7-digit DepEd Employee Number succeeds and saves department_id
        $this->postJson(route('api.v1.register.store'), [
            'member_role' => 'Faculty',
            'department_id' => $dept->department_id,
            'reference_number' => '4567890',
            'last_name' => 'Cruz',
            'first_name' => 'Elena',
            'email' => 'elena@ubnhs.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSuccessful();

        $this->assertDatabaseHas('library_member', [
            'reference_number' => '4567890',
            'department_id' => $dept->department_id,
        ]);
    }

    public function test_public_sign_up_rejects_personnel_roles_and_duplicate_reference_number(): void
    {
        Mail::fake();
        LibraryMember::factory()->create(['reference_number' => '136899999999']);

        $this->postJson(route('api.v1.register.store'), [
            'member_role' => 'superadmin',
            'reference_number' => '136899999999',
            'last_name' => 'Test',
            'first_name' => 'User',
            'username' => 'badroleuser',
            'email' => 'badrole@ubnhs.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertJsonValidationErrors(['member_role', 'reference_number']);
    }

    public function test_invalid_or_expired_otp_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->withRole(Rbac::FACULTY)->create();
        $user->emailOtps()->create([
            'code' => '123456',
            'expires_at' => now()->subMinute(),
        ]);

        $this->withSession(['otp_user_id' => $user->id])
            ->postJson(route('api.v1.otp.verify'), ['code' => '123456'])
            ->assertJsonValidationErrors('code');

        $this->assertGuest();
    }

    public function test_login_works_with_username_or_email_and_enforces_three_per_minute_rate_limit(): void
    {
        $user = User::factory()->withRole(Rbac::SUPERADMIN)->create([
            'username' => 'headlib',
            'email' => 'head@ubnhs.test',
            'password' => 'secret123',
        ]);

        // 3 failed attempts are allowed; 4th hits 429 rate limit.
        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('api.v1.login.attempt'), [
                'login' => 'headlib',
                'password' => 'wrong-password',
            ])->assertJsonValidationErrors('login');
        }

        $this->postJson(route('api.v1.login.attempt'), [
            'login' => 'headlib',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        // Using email (distinct key) succeeds cleanly.
        $this->postJson(route('api.v1.login.attempt'), [
            'login' => 'head@ubnhs.test',
            'password' => 'secret123',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);

        // Logout clears session.
        $this->postJson(route('api.v1.logout'))->assertOk();
        $this->assertGuest();
    }
}
