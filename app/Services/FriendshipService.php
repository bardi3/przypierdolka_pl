<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Friendship;
use App\Models\User;

/**
 * Zaproszenia do znajomych: wysyłanie, akceptacja, odrzucenie, usuwanie.
 */
final class FriendshipService
{
    public const STATE_SELF            = 'self';
    public const STATE_NONE            = 'none';
    public const STATE_PENDING_SENT    = 'pending_sent';
    public const STATE_PENDING_RECEIVED = 'pending_received';
    public const STATE_FRIENDS         = 'friends';

    private Friendship $friendships;
    private User $users;

    public function __construct(?Friendship $friendships = null, ?User $users = null)
    {
        $this->friendships = $friendships ?? new Friendship();
        $this->users = $users ?? new User();
    }

    public function countFriends(int $userId): int
    {
        return $this->friendships->countAccepted($userId);
    }

    public function countPendingIncoming(int $userId): int
    {
        return $this->friendships->countPendingIncoming($userId);
    }

    /**
     * @return array{state:string, friendship_id:int|null}
     */
    public function relationState(?int $viewerId, int $profileUserId): array
    {
        if ($viewerId === null) {
            return ['state' => self::STATE_NONE, 'friendship_id' => null];
        }
        if ($viewerId === $profileUserId) {
            return ['state' => self::STATE_SELF, 'friendship_id' => null];
        }

        $row = $this->friendships->findBetween($viewerId, $profileUserId);
        if ($row === null) {
            return ['state' => self::STATE_NONE, 'friendship_id' => null];
        }

        $id = (int)$row['id'];
        return match ($row['status']) {
            Friendship::STATUS_ACCEPTED => ['state' => self::STATE_FRIENDS, 'friendship_id' => $id],
            Friendship::STATUS_PENDING  => (int)$row['requester_id'] === $viewerId
                ? ['state' => self::STATE_PENDING_SENT, 'friendship_id' => $id]
                : ['state' => self::STATE_PENDING_RECEIVED, 'friendship_id' => $id],
            default => ['state' => self::STATE_NONE, 'friendship_id' => null],
        };
    }

    public function areFriends(int $userA, int $userB): bool
    {
        $row = $this->friendships->findBetween($userA, $userB);

        return $row !== null && $row['status'] === Friendship::STATUS_ACCEPTED;
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function sendRequest(int $requesterId, int $addresseeId): array
    {
        if ($requesterId === $addresseeId) {
            return ['ok' => false, 'message' => 'Nie możesz dodać samego siebie.'];
        }

        if ($this->users->find($addresseeId) === null) {
            return ['ok' => false, 'message' => 'Użytkownik nie istnieje.'];
        }

        $existing = $this->friendships->findBetween($requesterId, $addresseeId);
        if ($existing !== null) {
            return match ($existing['status']) {
                Friendship::STATUS_ACCEPTED => ['ok' => false, 'message' => 'Już jesteście znajomymi.'],
                Friendship::STATUS_PENDING  => (int)$existing['requester_id'] === $requesterId
                    ? ['ok' => false, 'message' => 'Zaproszenie zostało już wysłane.']
                    : ['ok' => false, 'message' => 'Ten użytkownik wysłał Ci zaproszenie — zaakceptuj je.'],
                default => $this->resendAfterReject($existing, $requesterId, $addresseeId),
            };
        }

        $this->friendships->createRequest($requesterId, $addresseeId);

        return ['ok' => true, 'message' => 'Zaproszenie do znajomych wysłane.'];
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function accept(int $friendshipId, int $userId): array
    {
        $row = $this->friendships->find($friendshipId);
        if ($row === null || (int)$row['addressee_id'] !== $userId) {
            return ['ok' => false, 'message' => 'Nie można zaakceptować tego zaproszenia.'];
        }
        if ($row['status'] !== Friendship::STATUS_PENDING) {
            return ['ok' => false, 'message' => 'To zaproszenie nie jest już aktywne.'];
        }

        $this->friendships->updateStatus($friendshipId, Friendship::STATUS_ACCEPTED);

        return ['ok' => true, 'message' => 'Zaproszenie zaakceptowane.'];
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function reject(int $friendshipId, int $userId): array
    {
        $row = $this->friendships->find($friendshipId);
        if ($row === null) {
            return ['ok' => false, 'message' => 'Nie znaleziono zaproszenia.'];
        }

        $requesterId = (int)$row['requester_id'];
        $addresseeId = (int)$row['addressee_id'];
        if ($userId !== $requesterId && $userId !== $addresseeId) {
            return ['ok' => false, 'message' => 'Brak uprawnień.'];
        }

        if ($row['status'] === Friendship::STATUS_ACCEPTED) {
            $this->friendships->deletePair($requesterId, $addresseeId);

            return ['ok' => true, 'message' => 'Usunięto ze znajomych.'];
        }

        if ($row['status'] !== Friendship::STATUS_PENDING) {
            return ['ok' => false, 'message' => 'To zaproszenie nie jest już aktywne.'];
        }

        $this->friendships->updateStatus($friendshipId, Friendship::STATUS_REJECTED);

        return ['ok' => true, 'message' => 'Zaproszenie odrzucone.'];
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function removeFriend(int $userId, int $otherUserId): array
    {
        $row = $this->friendships->findBetween($userId, $otherUserId);
        if ($row === null || $row['status'] !== Friendship::STATUS_ACCEPTED) {
            return ['ok' => false, 'message' => 'Nie jesteście znajomymi.'];
        }

        $this->friendships->deletePair($userId, $otherUserId);

        return ['ok' => true, 'message' => 'Usunięto ze znajomych.'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listFriends(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->friendships->acceptedFriends($userId, $limit, $offset);
    }

    /**
     * @return array{incoming:array, outgoing:array}
     */
    public function pendingLists(int $userId): array
    {
        return [
            'incoming' => $this->friendships->pendingIncoming($userId),
            'outgoing' => $this->friendships->pendingOutgoing($userId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function profileFriendsPreview(int $profileUserId, int $limit = 12): array
    {
        return $this->friendships->friendsForProfile($profileUserId, $limit);
    }

    /**
     * @return array{ok:bool, message:string}
     */
    private function resendAfterReject(array $existing, int $requesterId, int $addresseeId): array
    {
        $this->friendships->deletePair($requesterId, $addresseeId);
        $this->friendships->createRequest($requesterId, $addresseeId);

        return ['ok' => true, 'message' => 'Zaproszenie do znajomych wysłane.'];
    }
}
