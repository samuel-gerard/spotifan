<?php

namespace App\Controller;

use App\Service\Spotify\SpotifyAuth;
use App\Service\Spotify\SpotifyRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    protected $spotifyAuthenticator;
    protected $spotifyRequest;

    public function __construct(SpotifyAuth $spotifyAuthenticator)
    {
        $this->spotifyAuthenticator = $spotifyAuthenticator;
    }

    /**
     * @Route("/default", name="default")
     */
    public function index(): Response
    {
        $authorizationUri = $this->spotifyAuthenticator->buildAuthorizationUri();

        return $this->redirect($authorizationUri);
    }

    /**
     * @Route("/login/oauth", name="callback")
     */
    public function callback(Request $request): Response
    {
        $accessToken = $this->spotifyAuthenticator->generateAccessToken();

        $this->spotifyRequest = new SpotifyRequest($accessToken);

        $data = $this->spotifyRequest->get('/artists/0TnOYISbd1XYRBk9myaseg');

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'data' => $data
        ]);
    }
}
