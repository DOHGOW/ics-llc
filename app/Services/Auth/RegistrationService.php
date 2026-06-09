<?php

namespace App\Services\Auth;

use App\Authorization\Roles;
use App\Events\Core\UserRegistered;
use App\Models\Core\User;
use Illuminate\Support\Facades\Hash;

/**
 * Self-registration (R-5 / D-047).
 *
 * A self-registrant may ONLY obtain a whitelisted, low-privilege role — never a
 * staff/admin/organisation role. Roles flagged as approval-required are created
 * with status='pending' and cannot authenticate until an admin approves them
 * (D-047). All others are created 'active' (subject to email verification).
 */
class RegistrationService
{
    /**
     * Whitelisted self-registration roles → requires-admin-approval flag.
     *
     * @var array<string,bool>
     */
    private const SELF_REGISTERABLE = [
        Roles::STUDENT => false,         // active immediately (verify email)
        Roles::STARTUP_FOUNDER => true,  // pending admin approval
    ];

    /** @param array{name:string,email:string,password:string,locale?:string} $data */
    public function register(array $data, string $requestedRole, string $ip): User
    {
        if (! array_key_exists($requestedRole, self::SELF_REGISTERABLE)) {
            throw new \DomainException('That role is not available for self-registration.');
        }

        $requiresApproval = self::SELF_REGISTERABLE[$requestedRole];

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'locale' => $data['locale'] ?? config('app.locale'),
            'status' => $requiresApproval ? 'pending' : 'active',
        ]);

        $user->assignRole($requestedRole);

        event(new UserRegistered($user, 'self', $ip));

        return $user;
    }

    /** @return array<int,string> Roles a visitor may choose at registration. */
    public static function whitelist(): array
    {
        return array_keys(self::SELF_REGISTERABLE);
    }
}
