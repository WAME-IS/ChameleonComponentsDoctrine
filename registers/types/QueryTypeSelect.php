<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Nette\InvalidArgumentException;
use Wame\ChameleonComponents\Definition\DataDefinition;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\ChameleonComponentsDoctrine\Registers\RelationsRegister;
use Wame\ChameleonComponentsDoctrine\Registers\Types\IRelation;
use Wame\ChameleonComponentsDoctrine\Utils\AliasGenerator;
use Wame\ChameleonComponentsDoctrine\Utils\Utils;
use Wame\Core\Entities\BaseEntity;
use Wame\Core\Registers\RepositoryRegister;

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
        $relationsHint = $dataSpace->getDataDefinition()->getHint('relations');
        $aliasGenerator = new AliasGenerator($qb);

        /*
         * Set relations
         */
        foreach ($dataSpace->getDataDefinition()->getRelations() as $target) {
            $knownCriteria = $dataSpace->getDataDefinition()->getRelations()[$target];

            $relation = $this->relationsRegister->getByTarget($dataSpace->getDataDefinition()->getTarget(), $target, $this->findHint($relationsHint, $target));

            if (!$relation) {
                $e = new InvalidArgumentException("Could not find relation.");
                $e->from = $dataSpace->getDataDefinition()->getTarget();
                $e->to = $target;
                throw $e;
            }

            $alias = $aliasGenerator->getAlias($target->getType());
            $relation->process($qb, $dataSpace, null, $alias);
            $qb->addCriteria(Utils::prefixCriteria($knownCriteria, $alias));
            $addedRelations[] = ['relation' => $relation, 'from' => $dataSpace, 'to' => null];
        }

        /*
         * Parent relations
         */
        $parent = $dataSpace;
        while ($parent = $parent->getParent()) {

            $to = $parent->getDataDefinition()->getTarget();

            $relation = $this->relationsRegister->getByTarget($dataSpace->getDataDefinition()->getTarget(), $to, $this->findHint($relationsHint, $to));

            if ($relation) {
                $alias = $aliasGenerator->getAlias($to->getType());
                $relation->process($qb, $dataSpace, $parent, $alias);
                $addedRelations[] = ['relation' => $relation, 'from' => $dataSpace, 'to' => $parent];
            }
        }

        return $addedRelations;
    }

    /**
     * 
     * @param array $relationsHint
     * @param DataDefinitionTarget $target
     * @return string
     */
    private function findHint($relationsHint, $target)
    {
        if ($relationsHint) {
            if (!is_array($relationsHint)) {
                $e = new InvalidArgumentException("Relations hint has to be associative array.");
                $e->hint = $relationsHint;
                throw $e;
            }
            return isset($relationsHint[$target->getType()]) ? : null;
        }
    }

    /**
     * @param BaseEntity|BaseEntity[] $result
     * @param IRelation[] $usedRelations
     */
    private function postProcessRelations($result, $usedRelations)
    {
        foreach ($usedRelations as $usedRelation) {
            $usedRelation['relation']->postProcess($result, $usedRelation['from'], $usedRelation['to']);
        }
        return $result;
    }
}
