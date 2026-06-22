<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Logika uprawnień oparta na rolach.
 *
 * Role: admin > moderator > user > guest
 */
final class Permissions
{
    public const ROLE_ADMIN     = 'admin';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_USER      = 'user';
    public const ROLE_GUEST     = 'guest';

    /** Hierarchia ról (im wyższa liczba, tym większe uprawnienia). */
    private const HIERARCHY = [
        self::ROLE_GUEST     => 0,
        self::ROLE_USER      => 1,
        self::ROLE_MODERATOR => 2,
        self::ROLE_ADMIN     => 3,
    ];

    public static function roles(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_MODERATOR, self::ROLE_USER];
    }

    public static function level(string $role): int
    {
        return self::HIERARCHY[$role] ?? 0;
    }

    /**
     * Czy rola ma co najmniej takie uprawnienia jak wymagane.
     */
    public static function atLeast(?string $role, string $required): bool
    {
        return self::level($role ?? self::ROLE_GUEST) >= self::level($required);
    }

    public static function isAdmin(?string $role): bool
    {
        return $role === self::ROLE_ADMIN;
    }

    public static function isModerator(?string $role): bool
    {
        return self::atLeast($role, self::ROLE_MODERATOR);
    }

    /**
     * Czy użytkownik może moderować historie (akceptacja/odrzucanie).
     */
    public static function canModerate(?string $role): bool
    {
        return self::atLeast($role, self::ROLE_MODERATOR);
    }

    /**
     * Czy użytkownik może edytować daną historię.
     */
    public static function canEditStory(?string $role, ?int $userId, int $storyOwnerId): bool
    {
        if (self::canModerate($role)) {
            return true;
        }
        return $userId !== null && $userId === $storyOwnerId;
    }
}
