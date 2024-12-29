<?php

namespace Malik12tree\ZATCA\Exceptions;

class ComplianceException extends \Exception
{
    /** @var string */
    protected $status;

    /** @var array */
    protected $exceptions;

    private function __construct($status, $exceptions)
    {
        parent::__construct();

        $this->status = $status;
        $this->exceptions = $exceptions;
    }

    public function getExceptions()
    {
        return $this->exceptions;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public static function throwFromModel($includeWarnings, $response)
    {
        if (!is_array($response)) {
            return false;
        }

        $status = @$response['status'];
        $errorMessages = @$response['validationResults']['errorMessages'];
        $warningMessages = @$response['validationResults']['warningMessages'];

        if ($errorMessages) {
            foreach ($errorMessages as $error) {
                $exceptions[] = self::subException(false, $error);
            }
        }

        if ($includeWarnings && $warningMessages) {
            foreach ($warningMessages as $warning) {
                $exceptions[] = self::subException(true, $warning);
            }
        }

        if (count($exceptions) > 0) {
            throw new self($status, $exceptions);
        }

        return false;
    }

    private static function subException($isWarning, $error)
    {
        return [
            'category' => $error['category'],
            'code' => $error['code'],
            'message' => $error['message'],
            'status' => $isWarning ? 'WARNING' : 'ERROR',
        ];
    }
}
