<?php

namespace App\Services;

use App\Identity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Instagram;
use League\OAuth2\Client\Provider\LinkedIn;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Stevenmaguire\OAuth2\Client\Provider\Box;
use Stevenmaguire\OAuth2\Client\Provider\Elance;
use Stevenmaguire\OAuth2\Client\Provider\Eventbrite;
use Stevenmaguire\OAuth2\Client\Provider\Foursquare;
use Stevenmaguire\OAuth2\Client\Provider\Nest;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Stevenmaguire\OAuth2\Client\Provider\Paypal;
use Stevenmaguire\OAuth2\Client\Provider\Salesforce;
use Stevenmaguire\OAuth2\Client\Provider\Uber;
use Stevenmaguire\OAuth2\Client\Provider\Zendesk;

class OAuth2 extends Authentication
{
    /**
     * Credentials session key.
     *
     * @var string
     */
    protected $credentialsSessionKey = 'oauth2.credentials';

    /**
     * State session key.
     *
     * @var string
     */
    protected $stateSessionKey = 'oauth2.state';

    /**
     * Attempt to create OAuth2 client provider.
     *
     * @param  string  $provider
     * @param  array   $credentials
     *
     * @return League\OAuth2\Client\Provider\AbstractProvider|null
     */
    protected function getClientByProvider($provider, $credentials = [])
    {
        $providers = $this->getClientMap();

        if (isset($providers[$provider])) {
            return new $providers[$provider]($credentials);
        }

        return null;
    }

    /**
     * Retrieves array that maps client keys to client class.
     *
     * @return array
     */
    protected function getClientMap()
    {
        return [
            'bitbucket' => Bitbucket::class,
            'box' => Box::class,
            'facebook' => Facebook::class,
            'github' => Github::class,
            'instagram' => Instagram::class,
            'linkedin' => LinkedIn::class,
            'elance' => Elance::class,
            'eventbrite' => Eventbrite::class,
            'foursquare' => Foursquare::class,
            'microsoft' => Microsoft::class,
            'nest' => Nest::class,
            'paypal' => Paypal::class,
            'salesforce' => Salesforce::class,
            'uber' => Uber::class,
            'zendesk' => Zendesk::class,
        ];
    }

    /**
     * Retrieves array of currently configured clients.
     *
     * @return array
     */
    public function getSupportedClientKeys()
    {
        return array_keys($this->getClientMap());
    }

    /**
     * Engage in login flow.
     *
     * @param  string   $provider
     * @param  Request  $request
     *
     * @return RedirectResponse|Identity
     * @throws Exception
     */
    public function login($provider, Request $request)
    {
        $provider = strtolower($provider);
        $code = $request->input('code');
        $state = $request->input('state') ?: $this->getFromSession($this->stateSessionKey);
        $scopes = array_filter(array_map('trim', explode(',', $request->input('scopes'))));
        $credentials = [
            'key' => $request->input('key'),
            'secret' => $request->input('secret')
        ];

        $existingCredentials = $this->getFromSession($this->credentialsSessionKey);

        if (!empty($existingCredentials)) {
            $credentials = array_merge($credentials, $existingCredentials);
        }

        $client = $this->getClientByProvider($provider, [
            'clientId'          => $credentials['key'],
            'clientSecret'      => $credentials['secret'],
            'redirectUri'       => route('auth', ['protocol' => 'oauth2', 'provider' => $provider]),
            'isSandbox'         => true,
            'graphApiVersion'   => 'v2.5',
            'subdomain'         => 'delivered'
        ]);

        if (empty($client)) {
            throw new Exception('OAuth2 client exception');
        }

        if (empty($code)) {
            if (!empty($scopes)) {
                $authUrl = $client->getAuthorizationUrl([
                    'scope' => $scopes
                ]);
            } else {
                $authUrl = $client->getAuthorizationUrl();
            }

            $this->addToSession($this->credentialsSessionKey, $credentials);
            $this->addToSession($this->stateSessionKey, $client->getState());

            return redirect($authUrl);
        } elseif (empty($state)) {
            throw new Exception('OAuth2 flow exception, invalid state');
        } else {
            $identity = new Identity;

            $token = $client->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            if (is_a($client, Facebook::class)) {
                try {
                    $token = $client->getLongLivedAccessToken($token->getToken());
                    $identity->message = 'Exchanged your short-lived token for a '
                        .'long-lived token. This is a long-lived token.';
                } catch (Exception $e) {
                    $identity->message = 'This is a short-lived token.';
                }
            }

            $identity->accessToken = $token;

            try {
                $identity->resourceOwner = $client->getResourceOwner($token);
            } catch (Exception $e) {
                $identity->message = $e->getMessage();
                //throw new Exception('OAuth2 token exception');
            }

            return $identity;
        }
    }
}
