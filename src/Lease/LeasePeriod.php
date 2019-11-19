<?php

namespace SlaveMarket\Lease;

use DatePeriod;
use DateTime;
use SlaveMarket\Lease\Exception\LeaseHoursIntersectionsException;

/**
 * Период аренды, состоящий из арендованных часов (LeaseHour)
 *
 * @package SlaveMarket\Lease
 */
class LeasePeriod
{
    /** @var DatePeriod */
    protected $periods;

    /** @var LeaseHour[] */
    protected $leaseHours;

    public function __construct(DateTime $dateTimeFrom, DateTime $dateTimeTill)
    {
        $hoursFrom = $dateTimeFrom->format('H');
        $minutesFrom = $dateTimeFrom->format('i');
        $dateTimeFrom->setTime($hoursFrom, $minutesFrom, 0);

        $hoursTill = $dateTimeTill->format('H');
        $minutesTill = $dateTimeTill->format('i');
        $dateTimeTill->setTime($hoursTill, $minutesTill, 0);
        $dateTimeTill->modify('+ 1 hour');

        $this->periods = new DatePeriod($dateTimeFrom, new \DateInterval('PT1H'), $dateTimeTill);
    }

    /**
     * @param LeaseHour[] $leaseHours
     * @throws LeaseHoursIntersectionsException
     */
    public function checkLeaseHoursIntersections(array $leaseHours)
    {
        $intersectionDates = $this->getLeaseHoursIntersectionDates($leaseHours);
        if (!$intersectionDates) {
            return;
        }

        $intersectionsException = new LeaseHoursIntersectionsException();
        $intersectionsException->setIntersectionDates($intersectionDates);
        throw $intersectionsException;
    }

    /**
     * @param LeaseHour[] $comparingLeaseHours
     * @return array
     */
    protected function getLeaseHoursIntersectionDates(array $comparingLeaseHours): array
    {
        $errorTimes = [];
        foreach ($comparingLeaseHours as $comparingLeaseHour) {
            foreach ($this->getLeaseHours() as $selfLeaseHour) {
                if ($selfLeaseHour->isIntersectsWith($comparingLeaseHour)) {
                    $errorTimes[] = $comparingLeaseHour->getDateString();
                }
            }
        }
        return $errorTimes;
    }

    /**
     * @return LeaseHour[]
     */
    public function getLeaseHours(): array
    {
        if ($this->leaseHours) {
            return $this->leaseHours;
        }

        $this->leaseHours = [];

        foreach ($this->periods as $period) {
            $this->leaseHours[] = new LeaseHour($period->format(LeaseHour::DEFAULT_DATE_TIME_FORMAT));
        }

        return $this->leaseHours;
    }

    public function getDateTimeFrom()
    {
        return $this->periods->getStartDate();
    }

    public function getDateTimeTill()
    {
        return $this->periods->getEndDate();
    }
}
