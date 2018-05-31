<?php

namespace Wame\ChameleonComponentsDoctrine\Vendor\Wame\ChameleonComponents\Registers\Types;

use Nette\InvalidArgumentException;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\ChameleonComponents\IDataLoaderDriver;
use Wame\ChameleonComponentsDoctrine\Registers\QueryTypesRegister;
use Wame\ChameleonComponentsDoctrine\Registers\Types\IQueryType;

/**
 * @author Dominik Gmiterko <ienze@ienze.me>
 */
class DoctrineDataLoaderDriver implements IDataLoaderDriver
{

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
     * @param DataSpace $dataSpace
     * @return IQueryType
     * @throws InvalidArgumentException if corresponding query type is not found
     */
    private function getQueryType($dataSpace)
    {
        $queryTypeName = $dataSpace->getDataDefinition()->getQueryType();
        $queryType = $this->queryTypesRegister->getByName($queryTypeName);

        if (!$queryType) {
            throw new InvalidArgumentException("Query type with name $queryTypeName isn't supported");
        }

        return $queryType;
    }
}
