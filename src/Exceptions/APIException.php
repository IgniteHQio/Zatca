<?php

namespace Malik12tree\ZATCA\Exceptions;

class APIException extends \Exception
{
    protected $response;

    public function __construct($message, $code, $response = null)
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
