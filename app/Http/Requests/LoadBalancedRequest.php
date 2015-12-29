<?php

namespace App\Http\Requests;

use \Illuminate\Http\Request as Base;

class LoadBalancedRequest extends Base
{
    /**
     * Determine if the request is over HTTPS.
     *
     * @return bool
     */
    public function isSecure()
    {
        $isSecure = parent::isSecure();

        if ($isSecure) {
            return true;
        }

        if ($this->isHttpsOn()) {
            return true;
        } elseif ($this->isLoadBalancedHttps()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the request is using https scheme.
     *
     * @return bool
     */
    protected function isHttpsOn()
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    }

    /**
     * Determine if the request is using https via proxy.
     *
     * @return bool
     */
    protected function isLoadBalancedHttps()
    {
        return !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
            || !empty($_SERVER['HTTP_X_FORWARDED_SSL'])
            && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on';
    }
}
