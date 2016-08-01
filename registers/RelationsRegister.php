<?php

namespace Wame\ChameleonComponents\Drivers\DoctrineRepository;

use Wame\ChameleonComponents\Definition\DataDefinitionTarget;

class RelationsRegister extends \Wame\Core\Registers\BaseRegister
{
    
    public function __construct(\Nette\DI\Container $container)
    {
        $this->loadFromRepositories($container);
    }
    
    private function loadFromRepositories(\Nette\DI\Container $container)
    {
        $container->findByType(\Wame\Core\Repositories\BaseRepository::class);
    }
    
    
    /**
     * @param DataDefinitionTarget $from
     * @param DataDefinitionTarget $to
     */
    public function findRelation(DataDefinitionTarget $from, DataDefinitionTarget $to)
    {
        
    }
}
