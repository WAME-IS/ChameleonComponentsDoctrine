<?php

namespace Wame\ChameleonComponentsDoctrine\Loader;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Kdyby\Doctrine\EntityManager;
use Nette\DI\Container;
use Nette\InvalidStateException;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\ChameleonComponents\Drivers\DoctrineRepository\RelationsRegister;
use Wame\ChameleonComponentsDoctrine\Registers\Types\SimpleDoctrineRelation;
use Wame\Core\Repositories\BaseRepository;

class DoctrineRelationLoader
{

    /** @var Container */
    private $container;

    /** @var EntityManager */
    private $em;

    public function __construct(Container $container, EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    public function initialize(RelationsRegister $relationsRegister)
    {
        $repositoryNames = $this->container->findByType(BaseRepository::class);
        foreach ($repositoryNames as $repositoryName) {
            $repository = $this->container->getService($repositoryName);
            $this->loadFromRepository($repository, $relationsRegister);
        }
    }

    private function loadFromRepository(BaseRepository $repository, RelationsRegister $relationsRegister)
    {
        if (!$repository->getEntityName()) {
            $e = new InvalidStateException("Repository doesnt have entity set.");
            $e->repository = $repository;
            return $e;
        }

        $metadata = $this->em->getClassMetadata($repository->getEntityName());
        foreach ($metadata->getAssociationMappings() as $association) {

            list($manySource, $manyTarget) = $this->loadAssociationCardinality($association['type']);

            $relationsRegister->add(new SimpleDoctrineRelation(
                new DataDefinitionTarget($association['sourceEntity'], $manySource), $association['fieldName'], new DataDefinitionTarget($association['targetEntity'], $manyTarget), $association['mappedBy']
            ));
        }
    }

    private function loadAssociationCardinality($type)
    {
        switch ($type) {
            case ClassMetadataInfo::ONE_TO_ONE:
                return [false, false];
            case ClassMetadataInfo::ONE_TO_MANY:
                return [false, true];
            case ClassMetadataInfo::MANY_TO_ONE:
                return [true, false];
            case ClassMetadataInfo::MANY_TO_MANY:
                return [true, true];
        }
    }
}
