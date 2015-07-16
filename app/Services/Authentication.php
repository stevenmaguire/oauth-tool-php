<?php namespace App\Services;

use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

abstract class Authentication
{
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
     * Get local file system.
     *
     * @return League\Flysystem\Filesystem
     */
    protected function getFileSystem()
    {
        $adapter = new Local(storage_path().'/app');
        return new Filesystem($adapter);
    }

    /**
     * Get closure for writing state file to file system.
     *
     * @param  Filesystem  $filesystem
     * @param  string      $file
     *
     * @return callable
     */
    protected function writeStateFile(Filesystem $filesystem, $file)
    {
        return function ($data) use ($filesystem, $file) {
            $verb = $filesystem->has($file) ? 'update' : 'write';
            $filesystem->$verb($file, json_encode($data));
        };
    }

    /**
     * Get closure for reading state file from file system.
     *
     * @param  Filesystem  $filesystem
     * @param  string      $file
     *
     * @return callable
     */
    protected function readStateFile(Filesystem $filesystem, $file)
    {
        return function () use ($filesystem, $file) {
            if ($filesystem->has($file)) {
                return json_decode($filesystem->read($file), true);
            }

            return [];
        };
    }
}
