<?php

namespace SlaveMarket\Lease;

use SlaveMarket\Master;
use SlaveMarket\Slave;

/**
 * Договор аренды
 *
 * @package SlaveMarket\Lease
 */
class LeaseContract
{
    /** @var Master Хозяин */
    protected $master;

    /** @var Slave Раб */
    protected $slave;

    /** @var float Стоимость */
    protected $price = 0;

    /** @var LeasePeriod Список арендованных часов */
    protected $leasePeriod = [];

    public function __construct(Master $master, Slave $slave, float $price, LeasePeriod $leasePeriod)
    {
        $this->master      = $master;
        $this->slave       = $slave;
        $this->price       = $price;
        $this->leasePeriod = $leasePeriod;
    }

    /**
     * @return Master
     */
    public function getMaster(): Master
    {
        return $this->master;
    }

    /**
     * @return Slave
     */
    public function getSlave(): Slave
    {
        return $this->slave;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return LeasePeriod
     */
    public function getLeasePeriod(): LeasePeriod
    {
        return $this->leasePeriod;
    }
}
