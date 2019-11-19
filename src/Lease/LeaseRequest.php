<?php

namespace SlaveMarket\Lease;

use DateTime;
use SlaveMarket\Lease\Exception\LeaseRequestException;

/**
 * Запрос на аренду раба
 *
 * @package SlaveMarket\Lease
 */
class LeaseRequest
{
    const DEFAULT_TIME_FORMAT = 'Y-m-d H:i:s';

    /** @var int id хозяина */
    protected $masterId;

    /** @var int id раба */
    protected $slaveId;

    /** @var string время начала работ Y-m-d H:i:s */
    protected $timeFrom;

    /** @var string время окончания работ Y-m-d H:i:s */
    protected $timeTo;

    /** @var LeasePeriod */
    protected $leasePeriod;

    /**
     * LeaseRequest constructor.
     * @param int $masterId
     * @param int $slaveId
     * @param string $timeFrom
     * @param string $timeTo
     */
    public function __construct(int $masterId, int $slaveId, string $timeFrom, string $timeTo)
    {
        $this->masterId = $masterId;
        $this->slaveId = $slaveId;
        $this->timeFrom = $timeFrom;
        $this->timeTo = $timeTo;
    }

    /**
     * @return int
     */
    public function getMasterId(): int
    {
        return $this->masterId;
    }

    /**
     * @return int
     */
    public function getSlaveId(): int
    {
        return $this->slaveId;
    }

    /**
     * @return LeasePeriod
     * @throws LeaseRequestException
     */
    public function getLeasePeriod(): LeasePeriod
    {
        if (!$this->leasePeriod) {
            $dateTimeFrom = DateTime::createFromFormat(self::DEFAULT_TIME_FORMAT, $this->timeFrom);
            if (!$dateTimeFrom) {
                throw new LeaseRequestException(sprintf('Дата начала аренды должна быть передана в формате "%s"', self::DEFAULT_TIME_FORMAT));
            }

            $dateTimeTo = DateTime::createFromFormat(self::DEFAULT_TIME_FORMAT, $this->timeTo);
            if (!$dateTimeTo) {
                throw new LeaseRequestException(sprintf('Дата окончания аренды должна быть передана в формате "%s"', self::DEFAULT_TIME_FORMAT));
            }

            if ($dateTimeTo <= $dateTimeFrom) {
                throw new LeaseRequestException('Дата окончания аренды должна быть больше даты начала аренды');
            }

            $this->leasePeriod = new LeasePeriod($dateTimeFrom, $dateTimeTo);
        }

        return $this->leasePeriod;
    }
}