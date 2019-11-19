<?php

namespace SlaveMarket\Lease\Price;

use SlaveMarket\Lease\LeasePeriod;
use SlaveMarket\Master;
use SlaveMarket\Slave;

/**
 * Интерфейс калькуляторов, которые будут применяться для расчета стоимости аренды
 *
 * @package SlaveMarket\Lease\Price
 */
interface PriceCalculatorInterface
{
    /**
     * @param Master $master
     * @param Slave $slave
     * @param LeasePeriod $leasePeriod
     * @return float
     */
    public function getPrice(Master $master, Slave $slave, LeasePeriod $leasePeriod): float;
}
