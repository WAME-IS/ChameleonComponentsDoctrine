<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Nette\InvalidArgumentException as InvalidArgumentException2;
use RecursiveIteratorIterator;
use Wame\ChameleonComponents\Definition\DataDefinition;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\ChameleonComponents\Definition\RecursiveTreeDefinitionIterator;
use Wame\ChameleonComponentsDoctrine\Registers\RelationsRegister;
use Wame\Core\Entities\BaseEntity;
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
        list($qb, $usedRelations) = $this->prepareQuery($dataSpace);
        $query = $qb->getQuery();
        if ($dataSpace->getDataDefinition()->getTarget()->isList()) {
            return function() use ($query, $usedRelations) {
                return $this->postProcessRelations($query->getResult(), $usedRelations);
            };
        } else {
            return function() use ($query, $usedRelations) {
                return $this->postProcessRelations($query->setMaxResults(1)->getSingleResult(), $usedRelations);
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
            $this->addQueryHint($dataSpace, $qb);
            $relations = $this->addRelations($dataSpace, $qb);

            return [$qb, $relations];
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
    public function addQueryHint($dataSpace, $qb)
    {
        $query = $dataSpace->getDataDefinition()->getHint('query');
        if ($query) {
            if (is_array($query)) {
                foreach ($query as $q) {
                    call_user_func($q, $qb);
                }
            } else {
                call_user_func($query, $qb);
            }
        }
    }

    /**
     * @param DataSpace $dataSpace
     * @param QueryBuilder $qb
     * @return IRelation Added relations
     */
    public function addRelations($dataSpace, $qb)
    {
        $addedRelations = [];

        $relationHint = $dataSpace->getDataDefinition()->getHint('relation');
        array_merge($addedRelations, $this->addRelationFromHint($relationHint, $dataSpace, $qb));

        $parent = $dataSpace;
        while ($parent = $parent->getParent()) {

            $to = $parent->getDataDefinition()->getTarget();

            if (is_array($relationHint)) {
                $relationHint = isset($relationHint[$to->getType()]) ? $relationHint[$to->getType()] : null;
            }

            if ($relationHint && !is_string($relationHint)) {
                continue;
            }

            $relation = $this->relationsRegister->getByTarget($dataSpace->getDataDefinition()->getTarget(), $to, $relationHint);

            if ($relation) {
                $relation->process($qb, $dataSpace, $parent);
                $addedRelations[] = ['relation' => $relation, 'from' => $dataSpace, 'to' => $parent];
            }
        }

        return $addedRelations;
    }

    /**
     * @param DataSpace $dataSpace
     * @param QueryBuilder $qb
     * @return IRelation Added relations
     */
    private function addRelationFromHint($hint, $dataSpace, $qb)
    {
        $addedRelations = [];

        if (is_array($hint)) {
            foreach ($hint as $h) {
                array_merge($addedRelations, $this->addRelationFromHint($h, $dataSpace, $qb));
            }
            return;
        }

        if ($hint instanceof IRelation) {
            $toDataSpace = $this->getChildDataSpaceByType($dataSpace, $hint->getTo());
            if ($toDataSpace) {
                $e = new InvalidArgumentException2("No DataSpace for hinted relation found.");
                $e->hint = $hint;
                throw $e;
            }
            $hint->process($qb, $dataSpace, $toDataSpace);
            $addedRelations[] = ['relation' => $hint, 'from' => $dataSpace, 'to' => $toDataSpace];
        }

        return $addedRelations;
    }

    /**
     * @param DataSpace $dataSpace
     * @param DataDefinitionTarget $target
     */
    private function getChildDataSpaceByType($dataSpace, $target)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveTreeDefinitionIterator([$dataSpace]), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $dataSpace) {
            if ($dataSpace->getDataDefinition()->getTarget() == $target) {
                return $dataSpace;
            }
        }
    }

    /**
     * @param BaseEntity|BaseEntity[] $result
     * @param IRelation[] $usedRelations
     */
    private function postProcessRelations($result, $usedRelations)
    {
        foreach($usedRelations as $usedRelation) {
            $usedRelation['relation']->postProcess($result, $usedRelation['from'], $usedRelation['to']);
        }
        return $result;
    }
}
