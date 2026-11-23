<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\LibraryMember;
use App\Models\User;
use App\Services\OtpService;
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

    public function test_existing_pending_member_can_verify_otp_and_receive_member_qr(): void
    {
        Mail::fake();
        Storage::fake('local');

        $user = User::factory()->student()->pending()->create();
        LibraryMember::factory()->forAccount($user)->create();
        $user->assignMemberQr();
        app(OtpService::class)->issue($user);
        $this->withSession(['otp_user_id' => $user->getKey()]);
        $this->assertTrue($user->hasRole(Rbac::STUDENT));
        $this->assertSame(User::STATUS_PENDING, $user->account_status);

        $otpCode = null;
        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) use ($user, &$otpCode) {
            $otpCode = $mail->code;

            return $mail->hasTo($user->email) && strlen($mail->code) === 6;
        });

        $verifyResponse = $this->postJson(route('api.v1.otp.verify'), ['code' => $otpCode]);
        $verifyResponse->assertOk();
        $this->assertAuthenticatedAs($user);
        Storage::disk('local')->assertExists("qrcodes/members/{$user->user_qr_value}.png");
        Storage::disk('public')->assertMissing("qrcodes/members/{$user->user_qr_value}.png");

        $user->refresh();
        $member = $user->member->fresh();

        $this->assertSame(User::STATUS_ACTIVE, $user->account_status);
        $this->assertMatchesRegularExpression('/^UBNHS-\d{4}-\d{4}$/', $member->member_code);
        $this->assertEquals($member->member_code, $user->user_qr_value);

        $dash = $this->getJson(route('api.v1.me'));
        $dash->assertOk()->assertSee($member->member_code);
        $this->assertStringContainsString('no-store', $dash->headers->get('Cache-Control'));
    }



    public function test_invalid_or_expired_otp_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->pending()->withRole(Rbac::FACULTY)->create();
        app(OtpService::class)->issue($user);
        $code = Mail::sent(EmailOtpMail::class)->sole()->code;

        $this->travel(11)->minutes();
        $this->withSession(['otp_user_id' => $user->getKey()])
            ->postJson(route('api.v1.otp.verify'), ['code' => $code])
            ->assertJsonValidationErrors('code');

        $this->assertGuest();
    }

    public function test_email_login_has_a_three_per_minute_rate_limit(): void
    {
        $user = User::factory()->withRole(Rbac::SUPERADMIN)->create([
            'username' => 'headlib',
            'email' => 'head@ubnhs.test',
            'reference_number' => '1234567',
            'password_hash' => 'secret123',
        ]);

        foreach (['head@ubnhs.test', 'HEAD@ubnhs.test', ' head@ubnhs.test '] as $login) {
            $this->postJson(route('api.v1.login.attempt'), [
                'login' => $login,
                'password' => 'wrong-password',
            ])->assertJsonValidationErrors('login');
        }

        $this->postJson(route('api.v1.login.attempt'), [
            'login' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->post(route('api.v1.login.attempt'), [
            'login' => $user->email, 'password' => 'secret123',
        ])->assertStatus(429)->assertHeader('Retry-After')->assertSee('Too many login attempts');

        $this->travel(61)->seconds();
        $this->postJson(route('api.v1.login.attempt'), [
            'login' => 'head@ubnhs.test',
            'password' => 'secret123',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);

        $this->postJson(route('api.v1.logout'))->assertOk();
        $this->assertGuest();
    }

    public function test_username_and_reference_number_cannot_be_used_to_log_in(): void
    {
        $user = User::factory()->student()->create([
            'username' => 'localstudent', 'password_hash' => 'secret123',
        ]);
        foreach ([$user->username, $user->reference_number] as $login) {
            $this->postJson(route('api.v1.login.attempt'), ['login' => $login, 'password' => 'secret123'])
                ->assertUnprocessable()->assertJsonValidationErrors('login');
        }
        $this->assertGuest();
    }

    public function test_otp_cannot_verify_a_different_email_address(): void
    {
        Mail::fake();
        $user = User::factory()->pending()->student()->create();
        app(OtpService::class)->issue($user);
        $code = Mail::sent(EmailOtpMail::class)->sole()->code;
        $user->update(['email' => 'new-address@example.test']);

        $this->withSession(['otp_user_id' => $user->getKey()])
            ->postJson(route('api.v1.otp.verify'), ['code' => $code])
            ->assertJsonValidationErrors('code');
        $this->assertSame(User::STATUS_PENDING, $user->refresh()->account_status);
    }
}
