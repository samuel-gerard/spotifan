<?php

namespace App\Controller;

use App\Service\Spotify\SpotifyAuth;
use App\Service\Spotify\SpotifyRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DefaultController extends AbstractController
{
    protected $spotifyAuthenticator;
    protected $spotifyRequest;

    public function __construct(SpotifyAuth $spotifyAuthenticator, SpotifyRequest $spotifyRequest)
    {
        $this->spotifyAuthenticator = $spotifyAuthenticator;
        $this->spotifyRequest = $spotifyRequest;
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(): Response
    {
        $authorizationUri = $this->spotifyAuthenticator->buildAuthorizationUri();

        return $this->redirect($authorizationUri);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(SessionInterface $session): Response
    {
        $session->clear();

        return $this->redirectToRoute('homepage');
    }

    /** 
     * @Route("/login/oauth", name="callback")
     */
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');

        $this->spotifyRequest->setAuthenticator($this->spotifyAuthenticator);
        $this->spotifyAuthenticator->generateAccessToken($code);

        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/", name="homepage")
     */
    public function homepage(): Response
    {
        return $this->render('default/homepage.html.twig');
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboard(): Response
    {
        $tracks = $this->spotifyRequest->getUserTop('tracks', ['limit' => 5]);
        $artists = $this->spotifyRequest->getUserTop('artists', ['limit' => 5]);

        return $this->render('default/dashboard.html.twig', [
            'top_tracks' => $tracks,
            'top_artists' => $artists,
        ]);
    }

    /**
     * @Route("/me", name="user_profile")
     */
    public function userProfile(): Response
    {
        $user = $this->spotifyRequest->getMeProfile();

        return $this->render('default/user_profile.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/tracks", name="tracks")
     */
    public function getTopTracks(): Response
    {
        $data = $this->spotifyRequest->getUserTop('tracks');

        return $this->render('default/tracks.html.twig', [
            'data' => $data
        ]);
    }

    /**
     * @Route("/artists", name="artists")
     */
    public function getTopArtists(): Response
    {
        $data = $this->spotifyRequest->getUserTop('artists');

        return $this->render('default/artists.html.twig', [
            'data' => $data
        ]);
    }
}
