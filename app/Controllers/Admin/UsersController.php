<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\HttpException;
use App\Core\Permissions;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;

/**
 * Zarządzanie użytkownikami w panelu admina.
 */
final class UsersController extends AdminController
{
    private User $users;

    public function __construct(\App\Core\App $app)
    {
        parent::__construct($app);
        $this->users = new User($this->db);
    }

    public function index(): Response
    {
        $page = max(1, (int)$this->input('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $items = $this->users->paginated($perPage, $offset);
        $total = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `users`");

        return $this->view('admin/users/index', [
            'seo'   => $this->adminSeo('Użytkownicy'),
            'items' => $items,
            'page'  => $page,
            'pages' => (int)max(1, ceil($total / $perPage)),
            'roles' => Permissions::roles(),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin/users/edit', [
            'seo'    => $this->adminSeo('Nowy użytkownik'),
            'user'   => null,
            'roles'  => Permissions::roles(),
            'errors' => [],
        ]);
    }

    public function store(): Response
    {
        $this->verifyCsrf();

        $data = [
            'username' => trim((string)$this->input('username', '')),
            'email'    => trim((string)$this->input('email', '')),
            'password' => (string)$this->input('password', ''),
            'role'     => (string)$this->input('role', Permissions::ROLE_USER),
        ];

        $validator = new Validator($data, [
            'username' => 'nazwa', 'email' => 'e-mail', 'password' => 'hasło', 'role' => 'rola',
        ]);
        $validator->validate([
            'username' => 'required|min:3|max:30',
            'email'    => 'required|email|max:120',
            'password' => 'required|min:8|max:100',
            'role'     => 'required|in:' . implode(',', Permissions::roles()),
        ]);

        if ($validator->passes()) {
            if ($this->users->findByUsername($data['username']) !== null) {
                $validator->addError('username', 'Nazwa zajęta.');
            }
            if ($this->users->findByEmail($data['email']) !== null) {
                $validator->addError('email', 'E-mail zajęty.');
            }
        }

        if ($validator->fails()) {
            return $this->view('admin/users/edit', [
                'seo'    => $this->adminSeo('Nowy użytkownik'),
                'user'   => $data,
                'roles'  => Permissions::roles(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->users->createUser($data['username'], $data['email'], $data['password'], $data['role']);
        $this->session->flash('success', 'Użytkownik utworzony.');
        return $this->redirect($this->adminUrl('users'));
    }

    public function edit(string $id): Response
    {
        $user = $this->users->find((int)$id);
        if ($user === null) {
            throw HttpException::notFound('Użytkownik nie istnieje.');
        }
        return $this->view('admin/users/edit', [
            'seo'    => $this->adminSeo('Edycja użytkownika'),
            'user'   => $user,
            'roles'  => Permissions::roles(),
            'errors' => [],
        ]);
    }

    public function update(string $id): Response
    {
        $this->verifyCsrf();
        $userId = (int)$id;
        $user = $this->users->find($userId);
        if ($user === null) {
            throw HttpException::notFound('Użytkownik nie istnieje.');
        }

        $data = [
            'username' => trim((string)$this->input('username', '')),
            'email'    => trim((string)$this->input('email', '')),
            'role'     => (string)$this->input('role', $user['role']),
            'status'   => (string)$this->input('status', $user['status']),
            'password' => (string)$this->input('password', ''),
        ];

        $validator = new Validator($data, [
            'username' => 'login',
            'email'    => 'e-mail',
            'role'     => 'rola',
            'status'   => 'status',
            'password' => 'hasło',
        ]);
        $validator->validate([
            'username' => 'required|min:3|max:30',
            'email'    => 'required|email|max:120',
            'role'     => 'required|in:' . implode(',', Permissions::roles()),
            'status'   => 'required|in:active,blocked',
        ]);
        if ($data['password'] !== '' && mb_strlen($data['password']) < 8) {
            $validator->addError('password', 'Hasło musi mieć co najmniej 8 znaków.');
        }

        if ($validator->passes()) {
            if ($this->users->usernameExistsForOther($data['username'], $userId)) {
                $validator->addError('username', 'Ten login jest już zajęty.');
            }
            if ($this->users->emailExistsForOther($data['email'], $userId)) {
                $validator->addError('email', 'Ten e-mail jest już zarejestrowany.');
            }
        }

        if ($validator->fails()) {
            return $this->view('admin/users/edit', [
                'seo'    => $this->adminSeo('Edycja użytkownika'),
                'user'   => array_merge($user, $data),
                'roles'  => Permissions::roles(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = $data['role'];
        $status = $data['status'];
        $password = $data['password'];

        if ($user['role'] === Permissions::ROLE_ADMIN) {
            if ($role !== Permissions::ROLE_ADMIN && $this->countActiveAdmins() <= 1) {
                $this->session->flash('error', 'Nie można odebrać roli ostatniemu administratorowi.');
                return $this->back();
            }
            if ($status === 'blocked' && $this->countActiveAdmins() <= 1) {
                $this->session->flash('error', 'Nie można zablokować ostatniego administratora.');
                return $this->back();
            }
        }

        $this->users->updateProfile($userId, $data['username'], $data['email']);
        $this->users->update($userId, ['role' => $role, 'status' => $status]);
        if ($password !== '') {
            $this->users->updatePassword($userId, $password);
        }

        if ($userId === $this->auth->id()) {
            $this->auth->refreshFromDatabase();
        }

        $this->session->flash('success', 'Użytkownik zaktualizowany.');
        return $this->redirect($this->adminUrl('users'));
    }

    public function delete(string $id): Response
    {
        $this->verifyCsrf();
        $userId = (int)$id;

        if ($userId === $this->auth->id()) {
            $this->session->flash('error', 'Nie możesz usunąć własnego konta.');
            return $this->back();
        }

        $user = $this->users->find($userId);
        if ($user === null) {
            throw HttpException::notFound('Użytkownik nie istnieje.');
        }

        if ($user['role'] === Permissions::ROLE_ADMIN && $this->countActiveAdmins() <= 1) {
            $this->session->flash('error', 'Nie można usunąć ostatniego administratora.');
            return $this->back();
        }

        $this->users->delete($userId);
        $this->session->flash('success', 'Użytkownik usunięty.');
        return $this->back();
    }

    private function countActiveAdmins(): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `users` WHERE role = ? AND status = 'active'",
            [Permissions::ROLE_ADMIN]
        );
    }
}
