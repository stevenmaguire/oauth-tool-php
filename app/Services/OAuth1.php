<?php namespace App\Services;

use App\Identity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use League\OAuth1\Client\Server\Bitbucket;
use League\OAuth1\Client\Server\Magento;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\Trello;
use League\OAuth1\Client\Server\Tumblr;
use League\OAuth1\Client\Server\Twitter;
use League\OAuth1\Client\Server\Uservoice;

class OAuth1 extends Authentication
{
    /**
     * Credentials session key.
     *
     * @var string
     */
    protected $credentialsSessionKey = 'oauth1.credentials';

    /**
     * Temporary credentials session key.
     *
     * @var string
     */
    protected $tempCredentialsSessionKey = 'oauth1.tempCredentials';

    /**
     * Retrieves array that maps server keys to server class.
     *
     * @return array
     */
    protected function getServerMap()
    {
        return [
            'bitbucket' => Bitbucket::class,
            'magento' => Magento::class,
            'trello' => Trello::class,
            'tumblr' => Tumblr::class,
            'twitter' => Twitter::class,
            'uservoice' => Uservoice::class,
        ];
    }

    /**
     * Retrieves array of currently configured clients.
     *
     * @return array
     */
    public function getSupportedClientKeys()
    {
        return array_keys($this->getServerMap());
    }

    /**
     * Attempt to create OAuth1 server provider.
     *
     * @param  string  $provider
     * @param  array   $credentials
     *
     * @return League\OAuth1\Client\Server\Server|null
     */
    protected function getServerByProvider($provider, $credentials = [])
    {
        $servers = $this->getServerMap();

        if (isset($servers[$provider])) {
            return new $servers[$provider]($credentials);
        }

        return null;
    }

    /**
     * Get value from session by key.
     *
     * @param  string   $key
     * @param  mixed    $value
     *
     * @return boolean
     */
    protected function addToSession($key, $value)
    {
        $value = serialize($value);

        return parent::addToSession($key, $value);
    }

    /**
     * Get value from session by key.
     *
     * @param  string   $key
     * @param  boolean  $remove Optional
     *
     * @return mixed|null
     */
    protected function getFromSession($key, $remove = true)
    {
        $value = parent::getFromSession($key, $remove);

        return unserialize($value);
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
        $scopes = trim($request->input('scopes'));
        $oauthToken = $request->input('oauth_token');
        $oauthVerifier = $request->input('oauth_verifier');
        $credentials = [
            'key' => $request->input('key'),
            'secret' => $request->input('secret'),
            'scopes' => $request->input('scopes'),
        ];

        $existingCredentials = $this->getFromSession($this->credentialsSessionKey);

        if ($existingCredentials) {
            $credentials = array_merge($credentials, $existingCredentials);
        }

        $server = $this->getServerByProvider($provider, [
            'identifier' => $credentials['key'],
            'secret' => $credentials['secret'],
            'callback_uri' => route('auth', ['protocol' => 'oauth1', 'provider' => $provider]),
            'scope' => $credentials['scopes'],
            'name' => 'OAuth Tool',
        ]);

        if (empty($server)) {
            throw new Exception('OAuth1 server exception');
        }

        if ($oauthToken && $oauthVerifier) {
            $temporaryCredentials = $this->getFromSession($this->tempCredentialsSessionKey);

            $tokenCredentials = $server->getTokenCredentials($temporaryCredentials, $oauthToken, $oauthVerifier);

            $user = $server->getUserDetails($tokenCredentials);

            $identity = new Identity;
            $identity->resourceOwner = $user;
            $identity->accessToken = $tokenCredentials;

            return $identity;
        } else {
            $temporaryCredentials = $server->getTemporaryCredentials();

            $this->addToSession($this->credentialsSessionKey, $credentials);
            $this->addToSession($this->tempCredentialsSessionKey, $temporaryCredentials);

            $server->authorize($temporaryCredentials);
        }
    }
}
