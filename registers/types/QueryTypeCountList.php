<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Nette\InvalidArgumentException;
use Wame\ChameleonComponents\DataSpacesBuilder;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\Core\Registers\RepositoryRegister;

class QueryTypeCountList implements IQueryType
{

    const STATUS_SUFFIX = 'count';

    /** @var RepositoryRegister */
    private $repositoryRegister;

    /** @var QueryTypeSelect */
    private $queryTypeSelect;

    public function __construct(RepositoryRegister $repositoryRegister, QueryTypeSelect $queryTypeSelect)
    {
        $this->repositoryRegister = $repositoryRegister;
        $this->queryTypeSelect = $queryTypeSelect;
    }

    /**
     * @param DataSpace $dataSpace
     */
    public function prepareCallback($dataSpace)
    {
        $selectDataSpace = $this->getSelectDataSpace($dataSpace);
        $qb = $this->queryTypeSelect->prepareQuery($selectDataSpace);

        $qb->select($qb->expr()->count("id"));
        $qb->setMaxResults(null);
        $qb->setFirstResult(null);

        $query = $qb->getQuery();

        return function() use ($query) {
            return $query->getSingleScalarResult();
        };
    }

    /**
     * @param DataSpace $dataSpace
     */
    public function canPrepare($dataSpace)
    {
        return boolval($this->getSelectDataSpace($dataSpace));
    }

    /**
     * @param DataSpace $dataSpace
     */
    public function getStatusName($dataSpace)
    {
        return $dataSpace->getDataDefinition()->getTarget()->getType() . '-' . self::STATUS_SUFFIX;
    }

    /**
     * @param DataSpace $dataSpace
     */
    public function getSelectDataSpace($dataSpace)
    {
        $type = $dataSpace->getDataDefinition()->getTarget()->getType();
        if ($type == DataSpacesBuilder::ANY_TYPE_CHAR) {
            while ($parent = $dataSpace->getParent()) {
                if ($parent->getDataDefinition()->getTarget()->isList()) {
                    return $parent;
                }
            }
        } else {
            if (!$dataSpace->getDataDefinition()->getTarget()->isList()) {
                throw new InvalidArgumentException("Cannot count single entity");
            }
            if ($this->queryTypeSelect->canPrepare($dataSpace)) {
                return $dataSpace;
            }
        }
    }
}
