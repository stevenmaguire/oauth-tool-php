<?php namespace App\Services;

use App\Identity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Instagram;
use League\OAuth2\Client\Provider\LinkedIn;
use Stevenmaguire\OAuth2\Client\Provider\Box;
use Stevenmaguire\OAuth2\Client\Provider\Eventbrite;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Stevenmaguire\OAuth2\Client\Provider\Uber;

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
        $providers = [
            'box' => Box::class,
            'github' => Github::class,
            'instagram' => Instagram::class,
            'linkedin' => LinkedIn::class,
            'eventbrite' => Eventbrite::class,
            'microsoft' => Microsoft::class,
            'uber' => Uber::class,
        ];

        if (isset($providers[$provider])) {
            return new $providers[$provider]($credentials);
        }

        return null;
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
        $scopes = array_map('trim', explode(',', $request->input('scopes')));
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
            'redirectUri'       => route('auth', ['protocol' => 'oauth2', 'provider' => $provider])
        ]);

        if (empty($client)) {
            throw new Exception('OAuth2 client exception');
        }

        if (empty($code)) {
            $authUrl = $client->getAuthorizationUrl([
                'scope' => $scopes
            ]);

            $this->addToSession($this->credentialsSessionKey, $credentials);
            $this->addToSession($this->stateSessionKey, $client->getState());

            return redirect($authUrl);
        } elseif (empty($state)) {
            throw new Exception('OAuth2 flow exception, invalid state');
        } else {
            $token = $client->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            try {
                $identity = new Identity;
                $identity->resourceOwner = $client->getResourceOwner($token);
                $identity->accessToken = $token;

                return $identity;
            } catch (Exception $e) {
                throw new Exception('OAuth2 token exception');
            }
        }
    }
}
