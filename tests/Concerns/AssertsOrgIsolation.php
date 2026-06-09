<?php

namespace Tests\Concerns;

use App\Models\Core\User;
use Illuminate\Support\Facades\DB;

/**
 * Reusable isolation-test helpers (Wave 1a harness). Future org-owned module tests
 * (Client Portal, Partner Portal, …) use these to satisfy the mandatory isolation
 * test requirement (W1-1).
 */
trait AssertsOrgIsolation
{
    protected function makeUser(?int $accountId = null, ?string $role = null, string $status = 'active'): User
    {
        $user = User::create([
            'name' => 'U',
            'email' => uniqid('u', true).'@x.test',
            'password' => bcrypt('Password!2345'),
            'status' => $status,
        ]);

        if ($accountId !== null) {
            // Ensure the parent crm_accounts row exists (core_users.account_id FK). Engine-parity:
            // real MySQL/MariaDB enforce this FK (sqlite did not), so seed the account first.
            DB::table('crm_accounts')->insertOrIgnore([
                'id' => $accountId,
                'name' => 'Account '.$accountId,
                'type' => 'client',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user->forceFill(['account_id' => $accountId])->save();
        }

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    protected function makeOrgUser(int $accountId, string $role): User
    {
        return $this->makeUser($accountId, $role);
    }

    protected function makeStaffUser(string $role): User
    {
        return $this->makeUser(null, $role);
    }
}
