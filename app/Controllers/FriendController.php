<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Models\User;
use App\Services\FriendshipService;

/**
 * Akcje znajomych z poziomu profilu publicznego.
 */
final class FriendController extends Controller
{
    private User $users;
    private FriendshipService $friends;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->users = new User($this->db);
        $this->friends = new FriendshipService();
    }

    public function sendRequest(string $username): Response
    {
        $this->verifyCsrf();
        $viewerId = $this->auth->id();
        if ($viewerId === null) {
            return $this->redirect('/logowanie');
        }

        $profile = $this->users->findPublicByUsername($username);
        if ($profile === null) {
            throw HttpException::notFound();
        }

        $result = $this->friends->sendRequest($viewerId, (int)$profile['id']);
        $this->session->flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/profil/' . $username);
    }

    public function accept(string $username): Response
    {
        $this->verifyCsrf();
        $viewerId = $this->auth->id();
        if ($viewerId === null) {
            return $this->redirect('/logowanie');
        }

        $profile = $this->users->findPublicByUsername($username);
        if ($profile === null) {
            throw HttpException::notFound();
        }

        $relation = $this->friends->relationState($viewerId, (int)$profile['id']);
        $friendshipId = $relation['friendship_id'];
        if ($friendshipId === null || $relation['state'] !== FriendshipService::STATE_PENDING_RECEIVED) {
            $this->session->flash('error', 'Brak zaproszenia do zaakceptowania.');
            return $this->redirect('/profil/' . $username);
        }

        $result = $this->friends->accept($friendshipId, $viewerId);
        $this->session->flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/profil/' . $username);
    }

    public function reject(string $username): Response
    {
        $this->verifyCsrf();
        $viewerId = $this->auth->id();
        if ($viewerId === null) {
            return $this->redirect('/logowanie');
        }

        $profile = $this->users->findPublicByUsername($username);
        if ($profile === null) {
            throw HttpException::notFound();
        }

        $relation = $this->friends->relationState($viewerId, (int)$profile['id']);
        $friendshipId = $relation['friendship_id'];
        if ($friendshipId === null) {
            $this->session->flash('error', 'Brak aktywnego zaproszenia.');
            return $this->redirect('/profil/' . $username);
        }

        $result = $this->friends->reject($friendshipId, $viewerId);
        $this->session->flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/profil/' . $username);
    }

    public function remove(string $username): Response
    {
        $this->verifyCsrf();
        $viewerId = $this->auth->id();
        if ($viewerId === null) {
            return $this->redirect('/logowanie');
        }

        $profile = $this->users->findPublicByUsername($username);
        if ($profile === null) {
            throw HttpException::notFound();
        }

        $result = $this->friends->removeFriend($viewerId, (int)$profile['id']);
        $this->session->flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $this->redirect('/profil/' . $username);
    }
}
