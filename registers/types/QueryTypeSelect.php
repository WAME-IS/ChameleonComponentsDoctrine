<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Wame\ChameleonComponents\Definition\DataDefinition;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\ChameleonComponentsDoctrine\Registers\RelationsRegister;
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
        $relationHint = $dataSpace->getDataDefinition()->getHint('relation');
        $this->addRelationFromHint($relationHint, $dataSpace, $qb);
        
        $parent = $dataSpace;
        while ($parent = $parent->getParent()) {
            
            $to = $parent->getDataDefinition()->getTarget();
            
            if(is_array($relationHint)) {
                $relationHint = isset($relationHint[$to->getType()]) ? $relationHint[$to->getType()] : null;
            }
            
            if($relationHint && !is_string($relationHint)) {
                continue;
            }
            
            $relation = $this->relationsRegister->getByTarget($dataSpace->getDataDefinition()->getTarget(), $to, $relationHint);
            
            if($relation) {
                $relation->apply($qb, $dataSpace, $parent);
            }
        }
    }
    
    /**
     * @param DataSpace $dataSpace
     * @param QueryBuilder $qb
     */
    private function addRelationFromHint($hint, $dataSpace, $qb) {
        
        if(is_array($hint)) {
            foreach($hint as $h) {
                $this->addRelationFromHint($h, $dataSpace, $qb);
            }
            return;
        }
        
        if($hint instanceof IRelation) {
            $hint->apply($qb, $dataSpace, $this->getChildDataSpaceByType($dataSpace, $hint->getTo()));
        }
    }
    
    /**
     * @param DataSpace $dataSpace
     * @param \Wame\ChameleonComponents\Definition\DataDefinitionTarget $target
     */
    private function getChildDataSpaceByType($dataSpace, $target)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveTreeDefinitionIterator([$dataSpace]), RecursiveIteratorIterator::SELF_FIRST);
        
        foreach($iterator as $dataSpace) {
            if($dataSpace->getDataDefinition()->getTarget() == $target) {
                return $dataSpace;
            }
        }
    }
}
