<?php

namespace SlaveMarket\Lease\Price;

use SlaveMarket\Lease\LeasePeriod;
use SlaveMarket\Master;
use SlaveMarket\Slave;

/**
 * Калькулятор, модифицирующий стоимость аренды раба с учетом VIP-статуса хозяина его нанимающего
 *
 * @package SlaveMarket\Lease\Price
 */
class VipPriceCalculator implements PriceCalculatorInterface
{
    /** @var PriceCalculatorInterface следующий калькулятор */
    private $next;

    /** @var float процент VIP-скидки (значение от 0% до 100%) */
    private $percent;

    /**
     * VipPriceCalculator constructor.
     * @param PriceCalculatorInterface $next
     * @param float $percent
     */
    public function __construct(PriceCalculatorInterface $next, float $percent)
    {
        $this->next = $next;
        $this->percent = $percent;
    }

    /**
     * @param Master $master
     * @param Slave $slave
     * @param LeasePeriod $leasePeriod
     * @return float
     */
    public function getPrice(Master $master, Slave $slave, LeasePeriod $leasePeriod): float
    {
        $price = $this->next->getPrice($master, $slave, $leasePeriod);

        if ($master->isVIP()) {
            $price = $price * (100 - $this->percent) / 100;
        }

        return $price;
    }
}
