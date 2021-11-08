<?php

namespace App\Controller;

use Kerox\OAuth2\Client\Provider\Spotify;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/connect/spotify", name="connect_spotify")
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry->getClient('spotify')->redirect([
            Spotify::SCOPE_USER_TOP_READ,
            Spotify::SCOPE_USER_READ_PRIVATE,
            Spotify::SCOPE_USER_READ_EMAIL
        ], 
        [
            'redirect_uri' => 'http://127.0.0.1:8000/connect/spotify/check',
            'state' => 'spotify_auth_state'
        ]);
    }

    /**
     * After going to spotify, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route("/connect/spotify/check", name="connect_spotify_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        $client = $clientRegistry->getClient('spotify');

        try {
            // the exact class depends on which provider you're using
            $user = $client->fetchUser();

            dump($client->getAccessToken());die;
            
            return $this->render('spotify/me.html.twig', ['user' => $user]);
            

            // do something with all this new power!
	        // e.g. $name = $user->getFirstName();
            dump($user); die;
            // ...
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            dump($e->getMessage()); die;
        }
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/spotify/top-track", name="spotify_top_track")
     */
    public function spotifyTopTracks(Request $request, SessionInterface $session)
    {        
        $provider = new Spotify([
            'clientId'     => $this->getParameter('spotify_client_id'),
            'clientSecret' => $this->getParameter('spotify_client_secret'),
            'redirect_uri' => 'http://127.0.0.1:8000/connect/spotify/check',
        ]);
        
        if (!isset($_GET['code'])) {
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => [
                    Spotify::SCOPE_USER_READ_EMAIL,
                ],
                'redirect_uri' => 'http://127.0.0.1:8000/connect/spotify/check',
                'state' => 'spotify_auth_state'
            ]);
            
            $_SESSION['oauth2state'] = $provider->getState();

            // dump($session->get('oauth2state'));die;
            
            // header('Location: ' . $authUrl);
            // exit;

        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            echo 'Invalid state.';
            exit;

        }

        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        $session->set('access_token', $token);

        dump($token);die;
    }
}
