<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Uwierzytelnianie: logowanie, wylogowanie, dostęp do bieżącego użytkownika.
 * Korzysta z password_hash / password_verify oraz regeneracji ID sesji.
 */
final class Auth
{
    private Session $session;
    private User $users;

    /** @var array<string, mixed>|null */
    private ?array $cachedUser = null;

    public function __construct(Session $session, User $users)
    {
        $this->session = $session;
        $this->users = $users;
    }

    /**
     * Próba logowania po loginie/e-mailu i haśle.
     */
    public function attempt(string $login, string $password): bool
    {
        $user = $this->users->findByLoginOrEmail($login);

        if ($user === null || empty($user['password_hash'])) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }

        // Rehash, jeśli zmieniły się parametry algorytmu
        $secConfig = Config::get('security.password', []);
        if (password_needs_rehash($user['password_hash'], $secConfig['algo'] ?? PASSWORD_DEFAULT, $secConfig['options'] ?? [])) {
            $this->users->updatePassword((int)$user['id'], $password);
        }

        $this->login($user);
        return true;
    }

    /**
     * @param array<string, mixed> $user
     */
    public function login(array $user): void
    {
        // Ochrona przed session fixation
        $this->session->regenerate(true);
        $this->session->set('user_id', (int)$user['id']);
        $this->session->set('user_role', $user['role'] ?? Permissions::ROLE_USER);
        $this->cachedUser = $user;
        $this->users->touchLastLogin((int)$user['id']);
    }

    public function logout(): void
    {
        $this->session->remove('user_id');
        $this->session->remove('user_role');
        $this->cachedUser = null;
        $this->session->regenerate(true);
    }

    public function check(): bool
    {
        return $this->session->get('user_id') !== null;
    }

    public function id(): ?int
    {
        $id = $this->session->get('user_id');
        return $id !== null ? (int)$id : null;
    }

    public function role(): string
    {
        if (!$this->check()) {
            return Permissions::ROLE_GUEST;
        }
        return (string)$this->session->get('user_role', Permissions::ROLE_USER);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }
        if ($this->cachedUser === null) {
            $this->cachedUser = $this->users->find($this->id() ?? 0);
        }
        return $this->cachedUser;
    }

    public function can(string $requiredRole): bool
    {
        return Permissions::atLeast($this->role(), $requiredRole);
    }

    /**
     * Odświeża rolę i status z bazy — ochrona przed nieaktualną sesją po blokadzie/degradacji.
     */
    public function refreshFromDatabase(): void
    {
        if (!$this->check()) {
            return;
        }

        $user = $this->users->find($this->id() ?? 0);
        if ($user === null || ($user['status'] ?? 'active') !== 'active') {
            $this->logout();
            return;
        }

        $this->session->set('user_role', $user['role'] ?? Permissions::ROLE_USER);
        $this->cachedUser = $user;
    }
}
