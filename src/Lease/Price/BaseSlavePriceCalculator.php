<?php

namespace SlaveMarket\Lease\Price;

use SlaveMarket\Lease\LeasePeriod;
use SlaveMarket\Master;
use SlaveMarket\Slave;

/**
 * Калькулятор, рассчитывающий стоимость аренды раба на определенный период времени
 *
 * @package SlaveMarket\Lease\Price
 */
class BaseSlavePriceCalculator implements PriceCalculatorInterface
{
    private $maxWorkingHoursPerDay;

    /**
     * SlavePriceCalculator constructor.
     * @param int $maxWorkingHoursPerDay
     */
    public function __construct(int $maxWorkingHoursPerDay)
    {
        $this->maxWorkingHoursPerDay = $maxWorkingHoursPerDay;
    }

    /**
     * @param Master $master
     * @param Slave $slave
     * @param LeasePeriod $leasePeriod
     * @return float
     */
    public function getPrice(Master $master, Slave $slave, LeasePeriod $leasePeriod): float
    {
        $payableLeaseHours = [];
        $hoursCountByDays = [];
        foreach ($leasePeriod->getLeaseHours() as $leaseHour) {
            $date = $leaseHour->getDate();
            $hoursCountByDays[$date] += 1;
            if ($hoursCountByDays[$date] > $this->maxWorkingHoursPerDay) {
                continue;
            }
            $payableLeaseHours[] = $leaseHour;
        }
        return count($payableLeaseHours) * $slave->getPricePerHour();
    }
}
