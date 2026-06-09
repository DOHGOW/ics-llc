<?php

namespace Tests\Feature\Isolation;

use App\Authorization\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\AssertsOrgIsolation;
use Tests\Support\IsoFixture;
use Tests\Support\IsoFixturePolicy;
use Tests\TestCase;

/**
 * Cross-organisation isolation harness (Wave 1a). Covers the four required cases:
 * enumeration, direct record access, NULL account, and staff bypass.
 */
class AccountIsolationTest extends TestCase
{
    use AssertsOrgIsolation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, RoleSeeder::class, RolePermissionSeeder::class]);

        Schema::create('iso_fixtures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('name');
        });

        IsoFixture::acrossAccounts()->insert([
            ['account_id' => 1, 'name' => 'A1'],
            ['account_id' => 1, 'name' => 'A2'],
            ['account_id' => 2, 'name' => 'B1'],
            ['account_id' => null, 'name' => 'ICS'],
        ]);

        Gate::policy(IsoFixture::class, IsoFixturePolicy::class);
    }

    public function test_enumeration_is_isolated_to_own_account(): void
    {
        $this->actingAs($this->makeOrgUser(1, Roles::CLIENT_ADMIN));
        $this->assertEqualsCanonicalizing(['A1', 'A2'], IsoFixture::pluck('name')->all());

        $this->actingAs($this->makeOrgUser(2, Roles::CLIENT_ADMIN));
        $this->assertEqualsCanonicalizing(['B1'], IsoFixture::pluck('name')->all());
    }

    public function test_direct_record_access_is_denied_cross_account(): void
    {
        $a = $this->makeOrgUser(1, Roles::CLIENT_ADMIN);

        $ownRow = IsoFixture::acrossAccounts()->where('account_id', 1)->first();
        $otherRow = IsoFixture::acrossAccounts()->where('account_id', 2)->first();

        $this->assertTrue($a->can('view', $ownRow));
        $this->assertFalse($a->can('view', $otherRow)); // Layer 2 policy denial
    }

    public function test_null_account_user_sees_no_other_org_rows(): void
    {
        $this->actingAs($this->makeUser(null, Roles::STUDENT));
        $names = IsoFixture::pluck('name')->all();

        $this->assertNotContains('A1', $names);
        $this->assertNotContains('B1', $names);
    }

    public function test_internal_staff_bypass_sees_all_accounts(): void
    {
        $this->actingAs($this->makeStaffUser(Roles::PLATFORM_ADMIN));
        $this->assertCount(4, IsoFixture::all());
    }

    public function test_org_user_create_is_stamped_with_own_account(): void
    {
        $this->actingAs($this->makeOrgUser(1, Roles::CLIENT_ADMIN));
        $row = IsoFixture::create(['name' => 'NEW']);
        $this->assertSame(1, (int) $row->account_id); // stamped, cannot forge another org
    }
}
