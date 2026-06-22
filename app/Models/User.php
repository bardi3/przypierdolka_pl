<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Model;
use App\Core\Permissions;

/**
 * Model użytkownika.
 */
final class User extends Model
{
    protected string $table = 'users';

    /**
     * @return array<string, mixed>|null
     */
    public function findByLoginOrEmail(string $login): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1",
            [$login, $login]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy(['email' => $email]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy(['username' => $username]);
    }

    /**
     * Tworzy użytkownika z bezpiecznie zahashowanym hasłem.
     */
    public function createUser(string $username, string $email, string $password, string $role = Permissions::ROLE_USER): int
    {
        return $this->insert([
            'username'      => $username,
            'email'         => $email,
            'password_hash' => $this->hash($password),
            'role'          => $role,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->update($id, ['password_hash' => $this->hash($password)]);
    }

    public function touchLastLogin(int $id): void
    {
        $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    public function updateProfile(int $id, string $username, string $email, ?string $bio = null): void
    {
        $data = [
            'username' => $username,
            'email'    => $email,
        ];
        if ($bio !== null) {
            $data['bio'] = $bio !== '' ? $bio : null;
        }
        $this->update($id, $data);
    }

    /**
     * @param array{profile_visibility?:string, stories_visibility?:string, friends_list_visibility?:string} $settings
     */
    public function updatePrivacy(int $id, array $settings): void
    {
        $allowed = ['public', 'friends', 'private'];
        $data = [];
        foreach (['profile_visibility', 'stories_visibility', 'friends_list_visibility'] as $key) {
            if (isset($settings[$key]) && in_array($settings[$key], $allowed, true)) {
                $data[$key] = $settings[$key];
            }
        }
        if ($data !== []) {
            $this->update($id, $data);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicByUsername(string $username): ?array
    {
        return $this->db->fetch(
            "SELECT id, username, role, created_at, bio,
                    profile_visibility, stories_visibility, friends_list_visibility
             FROM `users`
             WHERE username = ? AND status = 'active'
             LIMIT 1",
            [$username]
        );
    }

    public function usernameExistsForOther(string $username, int $excludeId): bool
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `users` WHERE username = ? AND id != ?",
            [$username, $excludeId]
        ) > 0;
    }

    public function emailExistsForOther(string $email, int $excludeId): bool
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `users` WHERE email = ? AND id != ?",
            [$email, $excludeId]
        ) > 0;
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $user = $this->find($id);
        if ($user === null || empty($user['password_hash'])) {
            return false;
        }
        return password_verify($password, $user['password_hash']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paginated(int $limit, int $offset): array
    {
        return $this->db->fetchAll(
            "SELECT id, username, email, role, status, created_at, last_login_at
             FROM `users` ORDER BY id DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchPublic(string $query, int $limit = 8): array
    {
        $prefix = $this->escapeLike($query) . '%';

        return $this->db->fetchAll(
            "SELECT id, username, bio
             FROM `users`
             WHERE status = 'active' AND username LIKE ?
             ORDER BY username ASC
             LIMIT ?",
            [$prefix, $limit]
        );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function hash(string $password): string
    {
        $cfg = Config::get('security.password', []);
        return password_hash($password, $cfg['algo'] ?? PASSWORD_DEFAULT, $cfg['options'] ?? []);
    }
}
