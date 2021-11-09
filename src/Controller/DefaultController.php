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
     * @Route("/", name="homepage")
     */
    public function homepage(): Response
    {
        return $this->render('default/homepage.html.twig');
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
     * @Route("/login/oauth", name="callback")
     */
    public function callback(Request $request, SessionInterface $session): Response
    {
        $code = $request->query->get('code');

        $this->spotifyAuthenticator->generateAccessToken($code);

        return $this->redirectToRoute('tracks');
    }

    /**
     * @Route("/tracks", name="tracks")
     */
    public function getTopTracks(Request $request, SessionInterface $session): Response
    {
        $data = $this->spotifyRequest->get('/me/top/tracks');

        return $this->render('default/tracks.html.twig', [
            'data' => $data
        ]);
    }

    /**
     * @Route("/artists", name="artists")
     */
    public function getTopArtists(Request $request, SessionInterface $session): Response
    {
        $data = $this->spotifyRequest->get('/me/top/artists');

        return $this->render('default/artists.html.twig', [
            'data' => $data
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(Request $request, SessionInterface $session): Response
    {
        $session->clear();

        return $this->render('default/homepage.html.twig');
    }
}