<?php

namespace SlaveMarket\Lease;

use DateTimeInterface;
use SlaveMarket\Lease\Exception\LeaseHoursIntersectionsException;
use SlaveMarket\Lease\Exception\LeaseRequestException;
use SlaveMarket\Lease\Price\PriceCalculatorInterface;
use SlaveMarket\Master;
use SlaveMarket\MastersRepository;
use SlaveMarket\Slave;
use SlaveMarket\SlavesRepository;

/**
 * Операция "Арендовать раба"
 *
 * @package SlaveMarket\Lease
 */
class LeaseOperation
{
    /**
     * @var LeaseContractsRepository
     */
    protected $contractsRepository;

    /**
     * @var MastersRepository
     */
    protected $mastersRepository;

    /**
     * @var SlavesRepository
     */
    protected $slavesRepository;

    /**
     * @var PriceCalculatorInterface
     */
    protected $priceCalculator;

    /**
     * LeaseOperation constructor.
     *
     * @param LeaseContractsRepository $contractsRepo
     * @param MastersRepository $mastersRepo
     * @param SlavesRepository $slavesRepo
     * @param PriceCalculatorInterface $priceCalculator
     */
    public function __construct(LeaseContractsRepository $contractsRepo, MastersRepository $mastersRepo, SlavesRepository $slavesRepo, PriceCalculatorInterface $priceCalculator)
    {
        $this->contractsRepository = $contractsRepo;
        $this->mastersRepository   = $mastersRepo;
        $this->slavesRepository    = $slavesRepo;
        $this->priceCalculator     = $priceCalculator;
    }

    /**
     * Выполнить операцию
     *
     * @param LeaseRequest $request
     * @return LeaseResponse
     */
    public function run(LeaseRequest $request): LeaseResponse
    {
        $response = new LeaseResponse();

        try {
            $slave = $this->getSlaveById($request->getSlaveId());

            $master = $this->getMasterById($request->getMasterId());

            $this->checkLeaseHoursIntersections($request);

            $leasePeriod = $request->getLeasePeriod();

            $contractPrice = $this->priceCalculator->getPrice($master, $slave, $leasePeriod);
            $leaseContract = new LeaseContract($master, $slave, $contractPrice, $leasePeriod);

            $response->setLeaseContract($leaseContract);
        } catch (LeaseHoursIntersectionsException $intersectionsException) {
            $response->addError('Ошибка. ' . $intersectionsException->getIntersectionErrorMessage($slave));
        } catch (LeaseRequestException $leaseRequestException) {
            $response->addError('Ошибка. ' . $leaseRequestException->getErrorMessage());
        }

        return $response;
    }

    /**
     * @param LeaseRequest $request
     * @throws LeaseHoursIntersectionsException
     * @throws LeaseRequestException
     */
    protected function checkLeaseHoursIntersections(LeaseRequest $request)
    {
        $contractsByRequestPeriod = $this->getContractsByRequest($request);
        if (!$contractsByRequestPeriod) {
            return;
        }

        foreach ($contractsByRequestPeriod as $leaseContract) {
            $request->getLeasePeriod()->checkLeaseHoursIntersections($leaseContract->getLeasePeriod()->getLeaseHours());
        }
    }

    /**
     * @param LeaseRequest $leaseRequest
     * @return LeaseContract[]
     * @throws LeaseRequestException
     */
    protected function getContractsByRequest(LeaseRequest $leaseRequest): array
    {
        return $this->contractsRepository->getForSlave(
            $leaseRequest->getSlaveId(),
            $leaseRequest->getLeasePeriod()->getDateTimeFrom()->format(LeaseContractsRepository::DEFAULT_DATE_FORMAT),
            $leaseRequest->getLeasePeriod()->getDateTimeTill()->format(LeaseContractsRepository::DEFAULT_DATE_FORMAT)
        );
    }

    /**
     * @param int $slaveId
     * @return Slave
     * @throws LeaseRequestException
     */
    protected function getSlaveById(int $slaveId): Slave
    {
        $slave = $this->slavesRepository->getById($slaveId);
        if (!$slave) {
            throw new LeaseRequestException("Неверный идентификатор раба #{$slaveId}");
        }
        return $slave;
    }

    /**
     * @param int $masterId
     * @return Master
     * @throws LeaseRequestException
     */
    protected function getMasterById(int $masterId): Master
    {
        $master = $this->mastersRepository->getById($masterId);
        if (!$master) {
            throw new LeaseRequestException("Неверный идентификатор хозяина #{$masterId}");
        }
        return $master;
    }
}