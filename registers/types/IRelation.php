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
     * @param QueryBuilder $qb Query
     * @param DataSpace $from
     * @param DataSpace $to
     * @param string $relationAlias Alias that should be used for relation
     */
    public function process(QueryBuilder $qb, $from, $to, $relationAlias);
    
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
