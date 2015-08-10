<?php namespace App\Services;

use Illuminate\Http\Request;

abstract class Authentication
{
    /**
     * Retrieves array of currently configured clients.
     *
     * @return array
     */
    abstract public function getSupportedClientKeys();

    /**
     * Engage in login flow.
     *
     * @param  string   $provider
     * @param  Request  $request
     *
     * @return RedirectResponse|Identity
     * @throws Exception
     */
    abstract public function login($provider, Request $request);

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
        $success = session([$key => $value]);
        session()->save();

        return $success;
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
        if ($remove) {
            $value = session()->pull($key);
        } else {
            $value = session($key);
        }

        session()->save();

        return $value;
    }
}
