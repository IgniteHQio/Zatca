<?php

namespace Malik12tree\ZATCA\EGSDatabases;

use Malik12tree\ZATCA\EGS;
use Malik12tree\ZATCA\EGSDatabase;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;

class EGSFileDatabase extends EGSDatabase
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getEGSPath($uuid)
    {
        if (!Crypto::isUUID($uuid)) {
            throw new \Exception('EGS UUID is not valid.');
        }

        return $this->path."/{$uuid}.json";
    }

    public function save($egs)
    {
        $json = json_encode($egs->toJSON());
        $uuid = $egs->getUUID();

        if (!file_exists($this->path)) {
            mkdir($this->path, 0777, true);
        } elseif (!is_dir($this->path)) {
            throw new \Exception("EGS path {$this->path} is not a directory.");
        }

        $path = $this->getEGSPath($uuid);
        file_put_contents($path, $json);
    }

    protected function _load($uuid)
    {
        $path = $this->getEGSPath($uuid);
        if (!file_exists($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if (!$json) {
            return null;
        }

        return new EGS(json_decode($json, true));
    }
}
