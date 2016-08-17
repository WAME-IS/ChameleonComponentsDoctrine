<?php

namespace Wame\ChameleonComponents\Drivers\DoctrineRepository;

use Nette\InvalidArgumentException;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\ChameleonComponents\IDataLoaderDriver;
use Wame\ChameleonComponentsDoctrine\Registers\Types\IQueryType;

/**
 * @author Dominik Gmiterko <ienze@ienze.me>
 */
class DoctrineDataLoaderDriver implements IDataLoaderDriver
{

    const DEFAULT_QUERY_TYPE = 'select';

    /** @var QueryTypesRegister */
    private $queryTypesRegister;

    public function __construct(QueryTypesRegister $queryTypesRegister)
    {
        $this->queryTypesRegister = $queryTypesRegister;
    }

    /**
     * Prepare callback for loading data
     * 
     * @param DataSpace $dataSpace
     * @return callable
     */
    public function prepareCallback($dataSpace)
    {
        return $this->getQueryType($dataSpace)->prepareCallback($dataSpace);
    }

    /**
     * Returns whenever this driver can prepare callback to load data
     * 
     * @param DataSpace $dataSpace
     * @return boolean
     */
    public function canPrepare($dataSpace)
    {
        return $this->getQueryType($dataSpace)->canPrepare($dataSpace);
    }

    /**
     * Returns name of status used to store returned value
     * 
     * @param DataSpace $dataSpace
     * @return string
     */
    public function getStatusName($dataSpace)
    {
        return $this->getQueryType($dataSpace)->getStatusName($dataSpace);
    }

    /**
     * @param DataSpace $dataSpace
     * @return IQueryType
     * @throws InvalidArgumentException if corresponding query type is not found
     */
    private function getQueryType($dataSpace)
    {
        $target = $dataSpace->getDataDefinition()->getTarget();
        $queryType = $this->queryTypesRegister->getByName($target->getQueryType() ? : self::DEFAULT_QUERY_TYPE);
        if (!$queryType) {
            throw new InvalidArgumentException("Query type with name {$target->getQueryType()} isn't supported");
        }
        return $queryType;
    }
}
