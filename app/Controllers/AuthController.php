<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Response;
use App\Core\Seo;
use App\Core\Validator;
use App\Models\User;

/**
 * Rejestracja, logowanie i wylogowanie.
 */
final class AuthController extends Controller
{
    private User $users;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->users = new User($this->db);
    }

    public function showLogin(): Response
    {
        return $this->view('auth/login', [
            'seo'    => $this->authSeo('Logowanie', '/logowanie'),
            'old'    => [],
            'errors' => [],
        ]);
    }

    public function login(): Response
    {
        $this->verifyCsrf();

        $login = trim((string)$this->input('login', ''));
        $password = (string)$this->input('password', '');
        $ip = $this->clientIp();

        if (!$this->rateLimiter->attempt('login_ip', $ip)) {
            $retry = $this->rateLimiter->retryAfter('login_ip', $ip);
            $this->session->flash('error', "Zbyt wiele prób logowania z tego adresu IP. Spróbuj za {$retry} s.");
            return $this->redirect('/logowanie');
        }

        $identifier = $ip . '|' . $login;
        if (!$this->rateLimiter->attempt('login', $identifier)) {
            $retry = $this->rateLimiter->retryAfter('login', $identifier);
            $this->session->flash('error', "Zbyt wiele prób logowania. Spróbuj za {$retry} s.");
            return $this->redirect('/logowanie');
        }

        $validator = new Validator(
            ['login' => $login, 'password' => $password],
            ['login' => 'login lub e-mail', 'password' => 'hasło']
        );
        $validator->validate([
            'login'    => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->view('auth/login', [
                'seo'    => $this->authSeo('Logowanie', '/logowanie'),
                'old'    => ['login' => $login],
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$this->auth->attempt($login, $password)) {
            $this->session->flash('error', 'Nieprawidłowy login lub hasło.');
            return $this->view('auth/login', [
                'seo'    => $this->authSeo('Logowanie', '/logowanie'),
                'old'    => ['login' => $login],
                'errors' => [],
            ], 401);
        }

        $this->rateLimiter->clear('login', $identifier);
        $this->rateLimiter->clear('login_ip', $ip);
        $this->session->flash('success', 'Zalogowano pomyślnie.');

        return $this->redirect('/');
    }

    public function showRegister(): Response
    {
        return $this->view('auth/register', [
            'seo'    => $this->authSeo('Rejestracja', '/rejestracja'),
            'old'    => [],
            'errors' => [],
        ]);
    }

    public function register(): Response
    {
        $this->verifyCsrf();

        $identifier = $this->clientIp();
        if (!$this->rateLimiter->attempt('register', $identifier)) {
            $this->session->flash('error', 'Zbyt wiele prób rejestracji. Spróbuj później.');
            return $this->redirect('/rejestracja');
        }

        $data = [
            'username'              => trim((string)$this->input('username', '')),
            'email'                 => trim((string)$this->input('email', '')),
            'password'              => (string)$this->input('password', ''),
            'password_confirmation' => (string)$this->input('password_confirmation', ''),
        ];

        $validator = new Validator($data, [
            'username' => 'nazwa użytkownika',
            'email'    => 'e-mail',
            'password' => 'hasło',
        ]);
        $validator->validate([
            'username' => 'required|min:3|max:30',
            'email'    => 'required|email|max:120',
            'password' => 'required|min:8|max:100|confirmed',
        ]);

        if ($validator->passes()) {
            if ($this->users->findByUsername($data['username']) !== null) {
                $validator->addError('username', 'Ta nazwa użytkownika jest już zajęta.');
            }
            if ($this->users->findByEmail($data['email']) !== null) {
                $validator->addError('email', 'Ten e-mail jest już zarejestrowany.');
            }
        }

        if ($validator->fails()) {
            return $this->view('auth/register', [
                'seo'    => $this->authSeo('Rejestracja', '/rejestracja'),
                'old'    => ['username' => $data['username'], 'email' => $data['email']],
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $this->users->createUser($data['username'], $data['email'], $data['password']);
        $user = $this->users->find($userId);
        if ($user !== null) {
            $this->auth->login($user);
        }

        $this->session->flash('success', 'Konto utworzone. Witaj na pokładzie!');
        return $this->redirect('/');
    }

    public function logout(): Response
    {
        $this->verifyCsrf();
        $this->auth->logout();
        $this->session->flash('success', 'Wylogowano.');
        return $this->redirect('/');
    }

    private function authSeo(string $title, string $path): Seo
    {
        return (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle($title)
            ->setRobots('noindex, nofollow')
            ->setCanonical($path);
    }
}
