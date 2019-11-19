<?php

namespace SlaveMarket\Lease\Exception;

/**
 * Исключение выбрасываемое в случае, когда выявлены ошибки в обрабатываемом обекте LeaseRequest
 *
 * @package SlaveMarket\Lease\Exception
 */
class LeaseRequestException extends \Exception
{
    public function __construct(string $errorMessage = '')
    {
        parent::__construct($errorMessage);
    }

    public function getErrorMessage(): string
    {
        return $this->getMessage();
    }
}
