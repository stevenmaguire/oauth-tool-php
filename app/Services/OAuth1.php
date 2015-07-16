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
     * Attempt to create OAuth1 server provider.
     *
     * @param  string  $provider
     * @param  array   $credentials
     *
     * @return League\OAuth1\Client\Server\Server|null
     */
    protected function getServerByProvider($provider, $credentials = [])
    {
        $servers = [
            'bitbucket' => Bitbucket::class,
            'magento' => Magento::class,
            'trello' => Trello::class,
            'tumblr' => Tumblr::class,
            'twitter' => Twitter::class,
            'uservoice' => Uservoice::class,
        ];

        if (isset($servers[$provider])) {
            return new $servers[$provider]($credentials);
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
        $scopes = trim($request->input('scopes'));
        $oauthToken = $request->input('oauth_token');
        $oauthVerifier = $request->input('oauth_verifier');
        $credentials = [
            'key' => $request->input('key'),
            'secret' => $request->input('secret'),
            'scopes' => $request->input('scopes'),
        ];

        $existingCredentials = session('credentials');

        if ($existingCredentials) {
            $credentials = array_merge($credentials, $existingCredentials);
        }

        $server = $this->getServerByProvider($provider, [
            'identifier' => $credentials['key'],
            'secret' => $credentials['secret'],
            'callback_uri' => route('auth', ['protocol' => 'oauth1', 'provider' => $provider]),
            'name' => 'OAuth Tool',
            'scope' => $credentials['secret'],
        ]);

        if (empty($server)) {
            throw new Exception('OAuth1 server exception');
        }

        if ($oauthToken && $oauthVerifier) {
            $temporaryCredentials = session('temporaryCredentials');

            $tokenCredentials = $server->getTokenCredentials(
                $temporaryCredentials,
                $oauthToken,
                $oauthVerifier
            );

            $user = $server->getUserDetails($tokenCredentials);
            dd($user);
        } else {
            $temporaryCredentials = $server->getTemporaryCredentials();
            session(['credentials' => $credentials]);
            session(['temporaryCredentials' => $temporaryCredentials]);
            session()->save();
            $server->authorize($temporaryCredentials);
        }
    }
}
