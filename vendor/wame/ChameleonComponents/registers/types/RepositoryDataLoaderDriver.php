<?php

namespace Wame\ChameleonComponents\Drivers\DoctrineRepository;

use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\QueryBuilder;
use Nette\InvalidArgumentException;
use Wame\ChameleonComponents\Definition\DataDefinition;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\ChameleonComponents\IDataLoaderDriver;
use Wame\Core\Registers\RepositoryRegister;

/**
 * @author Dominik Gmiterko <ienze@ienze.me>
 */
class RepositoryDataLoaderDriver implements IDataLoaderDriver
{

    /** @var RepositoryRegister */
    private $repositoryRegister;

    /** @var RelationFinder */
    private $relationFinder;

    /** @var EntityManager */
    private $em;

    public function __construct(RepositoryRegister $repositoryRegister, RelationFinder $relationFinder, EntityManager $em)
    {
        $this->repositoryRegister = $repositoryRegister;
        dump($this->repositoryRegister->getAll());
        $this->relationFinder = $relationFinder;
        $this->em = $em;
    }

    /**
     * Prepare callback for loading data
     * 
     * @param DataSpace $dataSpace
     * @return callable
     */
    public function prepareCallback($dataSpace)
    {
        $dataDefinition = $dataSpace->getDataDefinition();
        $entityName = $dataDefinition->getTarget()->getType();
        $repository = $this->repositoryRegister->getByName($entityName);
        if ($repository) {

            $qb = $repository->createQueryBuilder();

            $this->buildQuery($dataDefinition, $qb);
            $this->addRelations($dataSpace, $qb);

            $dql = $qb->getDQL();

            return function() use ($dql) {
                return $this->em->createQuery($dql)->execute();
            };
        } else {
            throw new InvalidArgumentException("Couldn't find repository for entity named $entityName");
        }
    }

    /**
     * 
     * @param DataDefinition $dataDefinition
     * @param QueryBuilder $qb
     */
    private function buildQuery($dataDefinition, $qb)
    {   
        $target = $dataDefinition->getTarget();

        $qb->where($dataDefinition->getKnownProperties());

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
            $relation = $this->relationFinder->findRelation($dataSpace->getDataDefinition()->getTarget(), $parent->getDataDefinition()->getTarget());
            $relation->apply($qb);
        }
    }

    /**
     * Returns whenever this driver can prepare callback to load data
     * 
     * @param DataSpace $dataSpace
     * @return boolean
     */
    public function canPrepare($dataSpace)
    {
        $target = $dataSpace->getDataDefinition()->getTarget();
        return boolval($this->repositoryRegister->getByName($target->getType()));
    }
}
