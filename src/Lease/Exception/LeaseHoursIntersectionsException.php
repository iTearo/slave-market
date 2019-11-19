<?php

namespace SlaveMarket\Lease\Exception;

use SlaveMarket\Slave;

/**
 * Исключение выбрасываемое в случае, когда у раба есть заключенный контракт пересекающийся по времени с запросом на аренду
 *
 * @package SlaveMarket\Lease\Exception
 */
class LeaseHoursIntersectionsException extends LeaseRequestException
{
    protected $intersectionDates;

    public function getErrorMessage(): string
    {
        return sprintf('Занятые часы: "%s"', implode('", "', $this->intersectionDates));
    }

    public function getIntersectionErrorMessage(Slave $slave)
    {
         return sprintf(
            'Раб #%d "%s" занят. %s',
            $slave->getId(),
            $slave->getName(),
            $this->getErrorMessage()
        );
    }

    public function setIntersectionDates(array $intersectionDates)
    {
        $this->intersectionDates = $intersectionDates;
    }

    public function getIntersectionDates()
    {
        return $this->intersectionDates;
    }
}
