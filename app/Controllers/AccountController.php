<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Seo;
use App\Core\Validator;
use App\Models\Category;
use App\Models\Story;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\FeedService;
use App\Services\FriendshipService;
use App\Services\ProfilePrivacyService;
use App\Services\RankingService;
use App\Services\StoryService;

/**
 * Panel użytkownika: profil, hasło, własne historie.
 */
final class AccountController extends Controller
{
    private User $users;
    private Story $stories;
    private StoryService $storyService;
    private FriendshipService $friends;
    private ProfilePrivacyService $privacy;
    private FeedService $feedService;
    private Category $categories;
    private AvatarService $avatars;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->users = new User($this->db);
        $this->stories = new Story($this->db);
        $perPage = (int)Config::get('app.per_page', 12);
        $this->storyService = new StoryService($this->stories, $this->cache, $perPage);
        $this->categories = new Category($this->db);
        $this->feedService = new FeedService(
            $this->storyService,
            new RankingService($this->stories, $this->cache, $perPage),
            $this->categories
        );
        $this->friends = new FriendshipService();
        $this->privacy = new ProfilePrivacyService($this->friends);
        $this->avatars = new AvatarService();
    }

    public function index(): Response
    {
        return $this->redirect('/konto/profil');
    }

    public function profile(): Response
    {
        $user = $this->requireUser();

        return $this->accountView('account/profile', [
            'user'           => $user,
            'old'            => [],
            'errors'         => [],
            'avatarSupported'=> $this->avatars->canProcess(),
        ], 'profile');
    }

    public function ajaxUploadAvatar(): Response
    {
        if ($denied = $this->rejectUnlessAjax()) {
            return $denied;
        }

        try {
            $this->verifyCsrf();
        } catch (\Throwable) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy token CSRF.'], 403);
        }

        $user = $this->requireUser();
        $userId = (int)$user['id'];

        if ($this->rateLimiter->tooManyAttempts('avatar_upload', (string)$userId)) {
            $retry = $this->rateLimiter->retryAfter('avatar_upload', (string)$userId);
            $msg = $retry > 60
                ? 'Za dużo zapisów awatara w ostatniej godzinie. Spróbuj za ok. ' . (int)ceil($retry / 60) . ' min.'
                : ($retry > 0
                    ? 'Za dużo zapisów awatara. Spróbuj za chwilę.'
                    : 'Za dużo zapisów awatara w ostatniej godzinie.');
            return $this->json(['success' => false, 'error' => $msg], 429);
        }

        $file = $_FILES['avatar'] ?? null;
        if (!is_array($file)) {
            return $this->json(['success' => false, 'error' => 'Nie wybrano pliku.'], 422);
        }

        $oldPath = !empty($user['avatar_path']) ? (string)$user['avatar_path'] : null;
        $result = $this->avatars->storeFromUpload($file, $userId, $oldPath);

        if (!$result['ok']) {
            return $this->json(['success' => false, 'error' => $result['error'] ?? 'Błąd zapisu.'], 422);
        }

        $this->rateLimiter->attempt('avatar_upload', (string)$userId);

        $this->users->updateAvatarPath($userId, (string)$result['path']);
        $this->auth->refreshFromDatabase();
        $this->storyService->clearAllCaches();

        return $this->json([
            'success' => true,
            'url'     => $result['url'],
            'path'    => $result['path'],
            'message' => 'Zdjęcie profilowe zapisane.',
        ]);
    }

    public function ajaxRemoveAvatar(): Response
    {
        if ($denied = $this->rejectUnlessAjax()) {
            return $denied;
        }

        try {
            $this->verifyCsrf();
        } catch (\Throwable) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy token CSRF.'], 403);
        }

        $user = $this->requireUser();
        $userId = (int)$user['id'];
        $oldPath = $this->users->clearAvatar($userId);

        if ($oldPath !== null) {
            $this->avatars->deleteFile($oldPath);
        }

        $this->auth->refreshFromDatabase();
        $this->storyService->clearAllCaches();

        return $this->json([
            'success' => true,
            'message' => 'Zdjęcie profilowe usunięte.',
        ]);
    }

    public function updateProfile(): Response
    {
        $this->verifyCsrf();
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $data = [
            'username' => trim((string)$this->input('username', '')),
            'email'    => trim((string)$this->input('email', '')),
            'bio'      => trim((string)$this->input('bio', '')),
        ];

        $validator = new Validator($data, [
            'username' => 'nazwa użytkownika',
            'email'    => 'e-mail',
            'bio'      => 'opis profilu',
        ]);
        $validator->validate([
            'username' => 'required|min:3|max:30',
            'email'    => 'required|email|max:120',
            'bio'      => 'max:500',
        ]);

        if ($validator->passes()) {
            if ($this->users->usernameExistsForOther($data['username'], $userId)) {
                $validator->addError('username', 'Ta nazwa użytkownika jest już zajęta.');
            }
            if ($this->users->emailExistsForOther($data['email'], $userId)) {
                $validator->addError('email', 'Ten e-mail jest już zarejestrowany.');
            }
        }

        if ($validator->fails()) {
            return $this->accountView('account/profile', [
                'user'   => array_merge($user, $data),
                'old'    => $data,
                'errors' => $validator->errors(),
            ], 'profile', 422);
        }

        $this->users->updateProfile($userId, $data['username'], $data['email'], $data['bio']);
        $this->auth->refreshFromDatabase();

        $this->session->flash('success', 'Dane konta zostały zaktualizowane.');
        return $this->redirect('/konto/profil');
    }

    public function password(): Response
    {
        $this->requireUser();

        return $this->accountView('account/password', [
            'errors' => [],
        ], 'password');
    }

    public function updatePassword(): Response
    {
        $this->verifyCsrf();
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $data = [
            'current_password'          => (string)$this->input('current_password', ''),
            'password'                  => (string)$this->input('password', ''),
            'password_confirmation'     => (string)$this->input('password_confirmation', ''),
        ];

        $validator = new Validator($data, [
            'current_password' => 'obecne hasło',
            'password'         => 'nowe hasło',
        ]);
        $validator->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|max:100|confirmed',
        ]);

        if ($validator->passes() && !$this->users->verifyPassword($userId, $data['current_password'])) {
            $validator->addError('current_password', 'Obecne hasło jest nieprawidłowe.');
        }

        if ($validator->fails()) {
            return $this->accountView('account/password', [
                'errors' => $validator->errors(),
            ], 'password', 422);
        }

        $this->users->updatePassword($userId, $data['password']);
        $this->session->flash('success', 'Hasło zostało zmienione.');
        return $this->redirect('/konto/haslo');
    }

    public function stories(): Response
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];
        $page = max(1, (int)$this->input('page', 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $items = $this->stories->forUser($userId, $perPage, $offset);
        $total = $this->stories->countForUser($userId);

        return $this->accountView('account/stories', [
            'items' => $items,
            'page'  => $page,
            'pages' => (int)max(1, ceil($total / $perPage)),
        ], 'stories');
    }

    public function withdrawStory(string $id): Response
    {
        $this->verifyCsrf();
        $userId = $this->auth->id();
        if ($userId === null) {
            return $this->redirect('/logowanie');
        }

        $storyId = (int)$id;
        $deleted = $this->stories->withdrawPendingByUser($storyId, $userId);

        if (!$deleted) {
            $this->session->flash('error', 'Nie można cofnąć tej historii (tylko własne oczekujące na moderację).');
            return $this->redirect('/konto/historie');
        }

        $this->storyService->clearAllCaches();
        $this->cache->clearByPrefix('admin');

        $this->session->flash('success', 'Historia została cofnięta i usunięta z kolejki moderacji.');
        return $this->redirect('/konto/historie');
    }

    public function friends(): Response
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];
        $pending = $this->friends->pendingLists($userId);

        return $this->accountView('account/friends', [
            'friends'  => $this->friends->listFriends($userId, 100, 0),
            'incoming' => $pending['incoming'],
            'outgoing' => $pending['outgoing'],
        ], 'friends');
    }

    public function acceptFriend(string $id): Response
    {
        $this->verifyCsrf();
        $userId = $this->auth->id();
        if ($userId === null) {
            return $this->redirect('/logowanie');
        }

        $result = $this->friends->accept((int)$id, $userId);
        $this->session->flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/konto/znajomi');
    }

    public function rejectFriend(string $id): Response
    {
        $this->verifyCsrf();
        $userId = $this->auth->id();
        if ($userId === null) {
            return $this->redirect('/logowanie');
        }

        $result = $this->friends->reject((int)$id, $userId);
        $this->session->flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/konto/znajomi');
    }

    public function removeFriend(string $id): Response
    {
        $this->verifyCsrf();
        $userId = $this->auth->id();
        if ($userId === null) {
            return $this->redirect('/logowanie');
        }

        $friendship = (new \App\Models\Friendship())->find((int)$id);
        if ($friendship === null) {
            $this->session->flash('error', 'Nie znaleziono znajomości.');
            return $this->redirect('/konto/znajomi');
        }

        $otherId = (int)$friendship['requester_id'] === $userId
            ? (int)$friendship['addressee_id']
            : (int)$friendship['requester_id'];

        $result = $this->friends->removeFriend($userId, $otherId);
        $this->session->flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/konto/znajomi');
    }

    public function privacy(): Response
    {
        $user = $this->requireUser();

        return $this->accountView('account/privacy', [
            'user'    => $user,
            'options' => ProfilePrivacyService::options(),
            'labels'  => $this->privacy,
        ], 'privacy');
    }

    public function updatePrivacy(): Response
    {
        $this->verifyCsrf();
        $user = $this->requireUser();
        $userId = (int)$user['id'];

        $settings = [
            'profile_visibility'      => (string)$this->input('profile_visibility', 'public'),
            'stories_visibility'      => (string)$this->input('stories_visibility', 'public'),
            'friends_list_visibility' => (string)$this->input('friends_list_visibility', 'friends'),
        ];

        $this->users->updatePrivacy($userId, $settings);
        $this->auth->refreshFromDatabase();

        $this->session->flash('success', 'Ustawienia prywatności zapisane.');
        return $this->redirect('/konto/prywatnosc');
    }

    /**
     * @return array<string, mixed>
     */
    private function requireUser(): array
    {
        $user = $this->auth->user();
        if ($user === null) {
            throw HttpException::forbidden('Musisz być zalogowany.');
        }
        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function accountView(string $template, array $data, string $activeNav, int $status = 200): Response
    {
        $data['accountNav'] = $activeNav;
        $canonical = match ($activeNav) {
            'password' => '/konto/haslo',
            'stories'  => '/konto/historie',
            'friends'  => '/konto/znajomi',
            'privacy'  => '/konto/prywatnosc',
            default    => '/konto/profil',
        };
        $data['seo'] = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle('Moje konto')
            ->setRobots('noindex, nofollow')
            ->setCanonical($canonical);

        return $this->view($template, array_merge($data, $this->pageMeta()), $status);
    }

    /**
     * @return array{categories:array, trendingStories:array}
     */
    private function pageMeta(): array
    {
        return [
            'categories'        => $this->cache->remember('home:categories', fn () => $this->categories->allWithCounts(), 600),
            'trendingStories'   => $this->feedService->trending(5),
        ];
    }
}
