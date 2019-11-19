<?php

namespace SlaveMarket\Lease;

use DateTime;
use PHPUnit\Framework\TestCase;
use SlaveMarket\Lease\Price\PriceCalculatorInterface;
use SlaveMarket\Lease\Price\BaseSlavePriceCalculator;
use SlaveMarket\Lease\Price\VipPriceCalculator;
use SlaveMarket\Master;
use SlaveMarket\MastersRepository;
use SlaveMarket\Slave;
use SlaveMarket\SlavesRepository;

/**
 * Тесты операции аренды раба
 *
 * @package SlaveMarket\Lease
 */
class LeaseOperationTest extends TestCase
{
    /**
     * Stub репозитория хозяев
     *
     * @param Master[] ...$masters
     * @return MastersRepository
     */
    private function makeFakeMasterRepository(...$masters): MastersRepository
    {
        $mastersRepository = $this->prophesize(MastersRepository::class);
        foreach ($masters as $master) {
            if ($master) {
                $mastersRepository->getById($master->getId())->willReturn($master);
            }
        }

        return $mastersRepository->reveal();
    }

    /**
     * Stub репозитория рабов
     *
     * @param Slave[] ...$slaves
     * @return SlavesRepository
     */
    private function makeFakeSlaveRepository(...$slaves): SlavesRepository
    {
        $slavesRepository = $this->prophesize(SlavesRepository::class);
        foreach ($slaves as $slave) {
            if ($slave) {
                $slavesRepository->getById($slave->getId())->willReturn($slave);
            }
        }

        return $slavesRepository->reveal();
    }

    /**
     * Возвращает конфигурацию калькулятора для использования в тестах
     *
     * @return PriceCalculatorInterface
     */
    private function getBaseCalculator()
    {
        $basePriceCalculator = new BaseSlavePriceCalculator(16);
        return new VipPriceCalculator($basePriceCalculator, 10);
    }

    /**
     * Если раб занят, то арендовать его не получится
     */
    public function test_periodIsBusy_failedWithOverlapInfo()
    {
        // -- Arrange
        {
            // Хозяева
            $master1    = new Master(1, 'Господин Боб');
            $master2    = new Master(2, 'сэр Вонючка');
            $masterRepo = $this->makeFakeMasterRepository($master1, $master2);

            // Раб
            $slave1    = new Slave(1, 'Уродливый Фред', 20);
            $slaveRepo = $this->makeFakeSlaveRepository($slave1);

            // Договор аренды. 1й хозяин арендовал раба
            $leaseContract1DateFrom = new DateTime('2017-01-01 00:00:00');
            $leaseContract1DateTill = new DateTime('2017-01-01 03:59:59');
            $leaseContract1 = new LeaseContract($master1, $slave1, 80, new LeasePeriod($leaseContract1DateFrom, $leaseContract1DateTill));

            // Stub репозитория договоров
            $contractsRepo = $this->prophesize(LeaseContractsRepository::class);
            $contractsRepo
                ->getForSlave($slave1->getId(), '2017-01-01', '2017-01-01')
                ->willReturn([$leaseContract1]);

            // Запрос на новую аренду. 2й хозяин выбрал занятое время
            $leaseRequest = new LeaseRequest($master2->getId(), $slave1->getId(), '2017-01-01 01:30:00', '2017-01-01 02:01:00');

            // Операция аренды
            $leaseOperation = new LeaseOperation($contractsRepo->reveal(), $masterRepo, $slaveRepo, $this->getBaseCalculator());
        }

        // -- Act
        $response = $leaseOperation->run($leaseRequest);

        // -- Assert
        $expectedErrors = ['Ошибка. Раб #1 "Уродливый Фред" занят. Занятые часы: "2017-01-01 01", "2017-01-01 02"'];

        $this->assertArraySubset($expectedErrors, $response->getErrors());
        $this->assertNull($response->getLeaseContract());
    }

    /**
     * Если раб бездельничает, то его легко можно арендовать
     *
     * @dataProvider data_test_idleSlave_successfullyLeased
     *
     * @param bool $isVip
     * @param int $contractPrice
     * @param int $leasedHours
     */
    public function test_idleSlave_successfullyLeased(bool $isVip, int $contractPrice, int $leasedHours)
    {
        // -- Arrange
        {
            // Хозяева
            $master1    = new Master(1, 'Господин Боб', $isVip);
            $masterRepo = $this->makeFakeMasterRepository($master1);

            // Раб
            $slave1    = new Slave(1, 'Уродливый Фред', 20);
            $slaveRepo = $this->makeFakeSlaveRepository($slave1);

            $contractsRepo = $this->prophesize(LeaseContractsRepository::class);
            $contractsRepo
                ->getForSlave($slave1->getId(), '2017-01-01', '2017-01-02')
                ->willReturn([]);

            // Запрос на новую аренду
            $leaseRequest = new LeaseRequest($master1->getId(), $slave1->getId(), '2017-01-01 01:30:00', '2017-01-02 02:01:00');

            // Операция аренды
            $leaseOperation = new LeaseOperation($contractsRepo->reveal(), $masterRepo, $slaveRepo, $this->getBaseCalculator());
        }

        // -- Act
        $response = $leaseOperation->run($leaseRequest);

        // -- Assert
        $this->assertEmpty($response->getErrors());
        $this->assertInstanceOf(LeaseContract::class, $response->getLeaseContract());
        $this->assertEquals($contractPrice, $response->getLeaseContract()->getPrice());
        $this->assertEquals($leasedHours, count($response->getLeaseContract()->getLeasePeriod()->getLeaseHours()));
    }

    public function data_test_idleSlave_successfullyLeased()
    {
        return [
            'Стоимость аренды без скидки, т.к. хозяин не VIP-клиент' => [
                'isVip' => false,
                'contractPrice' => 380,
                'leasedHours' => 26,
            ],
            'Стоимость аренды со скидкой, т.к. хозяин VIP-клиент' => [
                'isVip' => true,
                'contractPrice' => 342,
                'leasedHours' => 26,
            ],
        ];
    }

    /**
     * Проверяет возрат ошибок при передаче неправильных входных данных в операцию регистрации аренды
     *
     * @dataProvider data_test_leaseOperationErrors
     *
     * @param string $masterId
     * @param Master|null $master
     * @param string $slaveId
     * @param Slave|null $slave
     * @param string $timeFrom
     * @param string $timeTo
     * @param array $expectedErrors
     */
    public function test_leaseOperationErrors(string $masterId, ?Master $master, string $slaveId, ?Slave $slave, string $timeFrom, string $timeTo, array $expectedErrors)
    {
        // -- Arrange
        {
            $contractsRepo = $this->prophesize(LeaseContractsRepository::class);

            $masterRepo = $this->makeFakeMasterRepository($master);

            $slaveRepo = $this->makeFakeSlaveRepository($slave);

            $leaseOperation = new LeaseOperation($contractsRepo->reveal(), $masterRepo, $slaveRepo, $this->getBaseCalculator());
        }

        $leaseRequest = new LeaseRequest($masterId, $slaveId, $timeFrom, $timeTo);

        // -- Act
        $response = $leaseOperation->run($leaseRequest);

        // -- Assert
        $this->assertArraySubset($expectedErrors, $response->getErrors());
        $this->assertNull($response->getLeaseContract());
    }

    public function data_test_leaseOperationErrors()
    {
        return [
            'Ошибка при неверном идентификаторе раба' => [
                1,
                new Master(1, 'Господин Боб'),
                987654,
                null,
                '2018-01-01 00:00:00',
                '2018-01-01 01:01:01',
                ['Ошибка. Неверный идентификатор раба #987654'],
            ],
            'Ошибка при неверном идентификаторе хозяина' => [
                654321,
                null,
                1,
                new Slave(1, 'Уродливый Фред', 20),
                '2018-01-01 00:00:00',
                '2018-01-01 01:01:01',
                ['Ошибка. Неверный идентификатор хозяина #654321'],
            ],
            'Ошибка при передаче даты начала аренды в неправильном формате' => [
                1,
                new Master(1, 'Господин Боб'),
                1,
                new Slave(1, 'Уродливый Фред', 20),
                'qwerty',
                '2018-01-01 01:01:01',
                ['Ошибка. Дата начала аренды должна быть передана в формате "Y-m-d H:i:s"'],
            ],
            'Ошибка при передаче даты окончания аренды в неправильном формате' => [
                1,
                new Master(1, 'Господин Боб'),
                1,
                new Slave(1, 'Уродливый Фред', 20),
                '2018-01-01 01:01:01',
                'qwerty',
                ['Ошибка. Дата окончания аренды должна быть передана в формате "Y-m-d H:i:s"'],
            ],
            'Ошибка при передаче даты окончания аренды, которая меньше или равна дате начала аренды' => [
                1,
                new Master(1, 'Господин Боб'),
                1,
                new Slave(1, 'Уродливый Фред', 20),
                '2020-01-01 00:00:00',
                '2010-01-01 00:00:00',
                ['Ошибка. Дата окончания аренды должна быть больше даты начала аренды'],
            ],
        ];
    }
}