<?php namespace App\Http\Controllers;

use Exception;
use App\Services\OAuth1;
use App\Services\OAuth2;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    /**
     * Create controller instance.
     *
     * @param OAuth2  $oauth2
     * @param OAuth1  $oauth1
     */
    public function __construct(OAuth2 $oauth2, OAuth1 $oauth1)
    {
        $this->oauth2 = $oauth2;
        $this->oauth1 = $oauth1;
    }

    /**
     * Get available protocols.
     *
     * @return array
     */
    protected function getAvailableProtocols()
    {
        return [
            'oauth2' => $this->oauth2->getSupportedClientKeys(),
            'oauth1' => $this->oauth1->getSupportedClientKeys(),
        ];
    }

    /**
     * Attempt to get auth service for given protocol.
     *
     * @param  string  $protocol
     *
     * @return App\Services\Authentication|null
     */
    protected function getAuthService($protocol)
    {
        $protocols = [
            'oauth1' => $this->oauth1,
            'oauth2' => $this->oauth2,
        ];

        return $protocols[$protocol] ?: null;
    }

    /**
     * Checks if given subject is a response.
     *
     * @param  mixed $subject
     *
     * @return boolean
     */
    protected function isResponse($subject)
    {
        $responses = [Response::class, RedirectResponse::class];

        return is_object($subject) && in_array(get_class($subject), $responses);
    }

    /**
     * Get protocol choices to begin auth flow.
     *
     * @return Response
     */
    public function getAuthOptions()
    {
        return view('start', ['protocols' => $this->getAvailableProtocols()]);
    }

    /**
     * Handle auth form.
     *
     * @param  Request  $request
     *
     * @return Response|RedirectResponse
     */
    public function postAuth(Request $request)
    {
        $v = $this->getValidationFactory()->make($request->input(), [
            'protocol_provider' => 'required|regex:/[a-z0-9]+\:[a-z0-9]+/',
            'key' => 'required',
            'secret' => 'required',
        ]);

        $v->sometimes('scopes', 'required', function ($input) {
            return in_array($input->protocol_provider, ['oauth2:uber']);
        });

        if ($v->fails()) {
            $this->throwValidationException($request, $v);
        }

        list($protocol, $provider) = explode(':', $request->input('protocol_provider'));

        return $this->getAuth($protocol, $provider, $request);
    }

    /**
     * Handle auth request.
     *
     * @param  string   $protocol
     * @param  string   $provider
     * @param  Request  $request
     *
     * @return Response|RedirectResponse
     */
    public function getAuth($protocol, $provider, Request $request)
    {
        try {
            $service = $this->getAuthService($protocol);
            if ($service) {
                $result = $service->login($provider, $request);

                if ($this->isResponse($result)) {
                    return $result;
                }

                return view('identity', ['identity' => $result]);
            }

            abort(404);
        } catch (Exception $e) {
            return redirect()->route('start')->with('message', $e->getMessage());
        }
    }
}
