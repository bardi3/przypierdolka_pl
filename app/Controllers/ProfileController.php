<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Rating;
use App\Models\Story;
use App\Models\User;
use App\Services\FriendshipService;
use App\Services\ProfilePrivacyService;
use App\Services\RatingService;
use App\Services\SeoSchemaService;
use App\Services\StoryService;

/**
 * Publiczne profile użytkowników — układ tablicy (social feed).
 */
final class ProfileController extends Controller
{
    private User $users;
    private Story $stories;
    private StoryService $storyService;
    private RatingService $ratingService;
    private FriendshipService $friends;
    private ProfilePrivacyService $privacy;
    private int $perPage;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->perPage = (int)Config::get('app.per_page', 12);
        $this->users = new User($this->db);
        $storyModel = new Story($this->db);
        $this->stories = $storyModel;
        $this->storyService = new StoryService($storyModel, $this->cache, $this->perPage);
        $this->ratingService = new RatingService(new Rating($this->db), $storyModel, $this->storyService);
        $this->friends = new FriendshipService();
        $this->privacy = new ProfilePrivacyService($this->friends);
    }

    public function show(string $username): Response
    {
        $profile = $this->users->findPublicByUsername($username);
        if ($profile === null) {
            throw HttpException::notFound('Nie ma takiego profilu.');
        }

        $viewerId = $this->auth->id();
        $profileId = (int)$profile['id'];
        $isOwner = $viewerId !== null && $viewerId === $profileId;

        if (!$this->privacy->canViewProfile($viewerId, $profile) && !$isOwner) {
            return $this->view('profile/private', [
                'seo' => (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
                    ->setTitle('Profil prywatny')
                    ->setRobots('noindex, nofollow')
                    ->setCanonical('/profil/' . $username),
                'username' => $username,
            ]);
        }

        $canViewStories = $this->privacy->canViewStories($viewerId, $profile);
        $canViewFriends = $this->privacy->canViewFriendsList($viewerId, $profile);
        $relation = $this->friends->relationState($viewerId, $profileId);
        $friendsCount = $this->friends->countFriends($profileId);
        $stats = $this->stories->statsForUser($profileId);

        $page = max(1, (int)$this->input('page', 1));
        $recent = [];
        $total = 0;
        $pages = 1;

        if ($canViewStories) {
            $total = $this->stories->countPublishedByUser($profileId);
            $pages = (int)max(1, ceil($total / $this->perPage));
            if ($page > $pages && $total > 0) {
                throw HttpException::notFound('Strona nie istnieje.');
            }
            $offset = ($page - 1) * $this->perPage;
            $recent = $this->stories->publishedByUser($profileId, $this->perPage, $offset);
        }

        $friendsPreview = $canViewFriends
            ? $this->friends->profileFriendsPreview($profileId, 16)
            : [];

        $profileUrl = $this->url('/profil/' . $profile['username']);
        $canonicalPath = $page > 1 ? '/profil/' . $profile['username'] . '?page=' . $page : '/profil/' . $profile['username'];
        $pageUrl = $this->url($canonicalPath);
        $breadcrumbItems = [
            ['name' => 'Start', 'url' => $this->url('/')],
            ['name' => (string)$profile['username'], 'url' => $profileUrl],
        ];

        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle('Profil: ' . $profile['username'])
            ->setDescription(sprintf(
                'Tablica użytkownika %s — %d historii, %d znajomych.',
                $profile['username'],
                $stats['stories_count'],
                $friendsCount
            ))
            ->setCanonical($canonicalPath);

        if ($canViewStories && $recent !== []) {
            $seo->addJsonLd((new SeoSchemaService())->profilePage(
                $profile,
                $pageUrl,
                $recent,
                $breadcrumbItems,
                $page,
                $this->perPage
            ));
        }

        return $this->view('profile/show', [
            'seo'              => $seo,
            'profile'          => $profile,
            'stats'            => $stats,
            'friendsCount'     => $friendsCount,
            'friendsPreview'   => $friendsPreview,
            'feed'             => $recent,
            'userRatings'      => $canViewStories ? $this->loadUserRatings($recent) : [],
            'page'             => $page,
            'pages'            => $pages,
            'total'            => $total,
            'breadcrumbs'      => $breadcrumbItems,
            'friendState'      => $relation['state'],
            'friendshipId'     => $relation['friendship_id'],
            'isOwner'          => $isOwner,
            'canViewStories'   => $canViewStories,
            'canViewFriends'   => $canViewFriends,
        ]);
    }
}
