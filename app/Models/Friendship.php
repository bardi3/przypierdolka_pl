<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Relacje znajomych: zaproszenie → akceptacja / odrzucenie.
 */
final class Friendship extends Model
{
    protected string $table = 'friendships';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @return array<string, mixed>|null
     */
    public function findBetween(int $userA, int $userB): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `friendships`
             WHERE (requester_id = ? AND addressee_id = ?)
                OR (requester_id = ? AND addressee_id = ?)
             LIMIT 1",
            [$userA, $userB, $userB, $userA]
        );
    }

    public function createRequest(int $requesterId, int $addresseeId): int
    {
        return $this->insert([
            'requester_id' => $requesterId,
            'addressee_id' => $addresseeId,
            'status'       => self::STATUS_PENDING,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->update($id, ['status' => $status]);
    }

    public function deletePair(int $userA, int $userB): void
    {
        $this->db->execute(
            "DELETE FROM `friendships`
             WHERE (requester_id = ? AND addressee_id = ?)
                OR (requester_id = ? AND addressee_id = ?)",
            [$userA, $userB, $userB, $userA]
        );
    }

    public function countAccepted(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `friendships`
             WHERE status = 'accepted'
               AND (requester_id = ? OR addressee_id = ?)",
            [$userId, $userId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function acceptedFriends(int $userId, int $limit, int $offset): array
    {
        return $this->db->fetchAll(
            "SELECT f.id AS friendship_id, f.created_at AS friends_since,
                    u.id, u.username, u.bio, u.avatar_path, u.created_at
             FROM `friendships` f
             INNER JOIN `users` u ON u.id = IF(f.requester_id = ?, f.addressee_id, f.requester_id)
             WHERE f.status = 'accepted'
               AND (f.requester_id = ? OR f.addressee_id = ?)
               AND u.status = 'active'
             ORDER BY u.username ASC
             LIMIT ? OFFSET ?",
            [$userId, $userId, $userId, $limit, $offset]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingIncoming(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT f.id, f.created_at, u.id AS user_id, u.username
             FROM `friendships` f
             INNER JOIN `users` u ON u.id = f.requester_id
             WHERE f.addressee_id = ? AND f.status = 'pending' AND u.status = 'active'
             ORDER BY f.created_at DESC",
            [$userId]
        );
    }

    public function countPendingIncoming(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `friendships` WHERE addressee_id = ? AND status = 'pending'",
            [$userId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingOutgoing(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT f.id, f.created_at, u.id AS user_id, u.username
             FROM `friendships` f
             INNER JOIN `users` u ON u.id = f.addressee_id
             WHERE f.requester_id = ? AND f.status = 'pending' AND u.status = 'active'
             ORDER BY f.created_at DESC",
            [$userId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function friendsForProfile(int $profileUserId, int $limit): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar_path
             FROM `friendships` f
             INNER JOIN `users` u ON u.id = IF(f.requester_id = ?, f.addressee_id, f.requester_id)
             WHERE f.status = 'accepted'
               AND (f.requester_id = ? OR f.addressee_id = ?)
               AND u.status = 'active'
             ORDER BY u.username ASC
             LIMIT ?",
            [$profileUserId, $profileUserId, $profileUserId, $limit]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchFriends(int $userId, string $query, int $limit = 8): array
    {
        $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';

        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.avatar_path, u.bio
             FROM `friendships` f
             INNER JOIN `users` u ON u.id = IF(f.requester_id = ?, f.addressee_id, f.requester_id)
             WHERE f.status = 'accepted'
               AND (f.requester_id = ? OR f.addressee_id = ?)
               AND u.status = 'active'
               AND u.username LIKE ?
             ORDER BY u.username ASC
             LIMIT ?",
            [$userId, $userId, $userId, $prefix, $limit]
        );
    }
}
