<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Wame\ChameleonComponents\Definition\DataDefinition;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\ChameleonComponents\Drivers\DoctrineRepository\RelationsRegister;
use Wame\Core\Registers\RepositoryRegister;
use WebLoader\InvalidArgumentException;

class QueryTypeSelect implements IQueryType
{

    /** @var RepositoryRegister */
    private $repositoryRegister;

    /** @var RelationsRegister */
    private $relationsRegister;

    public function __construct(RepositoryRegister $repositoryRegister, RelationsRegister $relationsRegister)
    {
        $this->repositoryRegister = $repositoryRegister;
        $this->relationsRegister = $relationsRegister;
    }

    /**
     * @param DataSpace $dataSpace
     */
    public function prepareCallback($dataSpace)
    {
        $qb = $this->prepareQuery($dataSpace);
        $query = $qb->getQuery();
        if ($dataSpace->getDataDefinition()->getTarget()->isList()) {
            return function() use ($query) {
                return $query->getResult();
            };
        } else {
            return function() use ($query) {
                return $query->setMaxResults(1)->getSingleResult();
            };
        }
    }

    /**
     * 
     * @param DataSpace $dataSpace
     * @return QueryBuilder
     * @throws InvalidArgumentException
     */
    public function prepareQuery($dataSpace)
    {
        $target = $dataSpace->getDataDefinition()->getTarget();
        $repository = $this->repositoryRegister->getByName($target->getType());
        if ($repository) {
            $qb = $repository->createQueryBuilder();

            $this->buildQuery($dataSpace->getDataDefinition(), $qb);
            $this->addRelations($dataSpace, $qb);

            return $qb;
        } else {
            throw new InvalidArgumentException("Couldn't find repository for entity named {$target->getType()}");
        }
    }

    /**
     * @param DataSpace $dataSpace
     */
    public function canPrepare($dataSpace)
    {
        $target = $dataSpace->getDataDefinition()->getTarget();
        return boolval($this->repositoryRegister->getByName($target->getType()));
    }

    /**
     * @param DataSpace $dataSpace
     */
    public function getStatusName($dataSpace)
    {
        return $dataSpace->getDataDefinition()->getTarget()->getStatusName();
    }

    /**
     * 
     * @param DataDefinition $dataDefinition
     * @param QueryBuilder $qb
     */
    private function buildQuery($dataDefinition, $qb)
    {
        $target = $dataDefinition->getTarget();

        if ($dataDefinition->getKnownProperties()) {
            $qb->addCriteria($dataDefinition->getKnownProperties());
        }

        if (!$target->isList()) {
            $qb->setMaxResults(1);
        }
    }

    /**
     * @param DataSpace $dataSpace
     * @param QueryBuilder $qb
     */
    public function addRelations($dataSpace, $qb)
    {
        while ($parent = $dataSpace->getParent()) {
            $relation = $this->relationsRegister->getByTarget($dataSpace->getDataDefinition()->getTarget(), $parent->getDataDefinition()->getTarget());
            $relation->apply($qb);
        }
    }
}
