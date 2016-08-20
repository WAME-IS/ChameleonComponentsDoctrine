<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\ChameleonComponents\Definition\DataSpace;

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
    public function apply(QueryBuilder $qb, $from, $to);
}
