<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Sprawdza widoczność profilu, historii i listy znajomych.
 */
final class ProfilePrivacyService
{
    public const VIS_PUBLIC  = 'public';
    public const VIS_FRIENDS = 'friends';
    public const VIS_PRIVATE = 'private';

    private FriendshipService $friends;

    public function __construct(?FriendshipService $friends = null)
    {
        $this->friends = $friends ?? new FriendshipService();
    }

    public function canViewProfile(?int $viewerId, array $profileUser): bool
    {
        $ownerId = (int)$profileUser['id'];
        if ($viewerId !== null && $viewerId === $ownerId) {
            return true;
        }

        return $this->allows($viewerId, $ownerId, (string)($profileUser['profile_visibility'] ?? self::VIS_PUBLIC));
    }

    public function canViewStories(?int $viewerId, array $profileUser): bool
    {
        $ownerId = (int)$profileUser['id'];
        if ($viewerId !== null && $viewerId === $ownerId) {
            return true;
        }

        if (!$this->canViewProfile($viewerId, $profileUser)) {
            return false;
        }

        return $this->allows($viewerId, $ownerId, (string)($profileUser['stories_visibility'] ?? self::VIS_PUBLIC));
    }

    public function canViewFriendsList(?int $viewerId, array $profileUser): bool
    {
        $ownerId = (int)$profileUser['id'];
        if ($viewerId !== null && $viewerId === $ownerId) {
            return true;
        }

        if (!$this->canViewProfile($viewerId, $profileUser)) {
            return false;
        }

        return $this->allows($viewerId, $ownerId, (string)($profileUser['friends_list_visibility'] ?? self::VIS_FRIENDS));
    }

    public function visibilityLabel(string $value): string
    {
        return match ($value) {
            self::VIS_FRIENDS => 'Tylko znajomi',
            self::VIS_PRIVATE => 'Tylko ja',
            default           => 'Wszyscy',
        };
    }

    /**
     * @return list<string>
     */
    public static function options(): array
    {
        return [self::VIS_PUBLIC, self::VIS_FRIENDS, self::VIS_PRIVATE];
    }

    private function allows(?int $viewerId, int $ownerId, string $visibility): bool
    {
        return match ($visibility) {
            self::VIS_PRIVATE => false,
            self::VIS_FRIENDS => $viewerId !== null && $this->friends->areFriends($viewerId, $ownerId),
            default           => true,
        };
    }
}
