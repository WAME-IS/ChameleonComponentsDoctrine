<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\ChameleonComponents\Definition\DataSpace;
use Wame\Core\Entities\BaseEntity;

interface IRelation
{

    /**
     * @return DataDefinitionTarget
     */
    public function getFrom();

    /**
     * @return DataDefinitionTarget
     */
    public function getTo();

    /**
     * @param QueryBuilder $qb
     * @param DataSpace $from
     * @param DataSpace $to
     */
    public function process(QueryBuilder $qb, $from, $to);
    
    /**
     * @param BaseEntity[] $result
     * @param DataSpace $from
     * @param DataSpace $to
     */
    public function postProcess(&$result, $from, $to);
    
    
    /**
     * @param mixed $hint
     * @return boolean
     */
    public function matchHint($hint);
}
