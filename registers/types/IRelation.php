<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;

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
     */
    public function apply(QueryBuilder $qb);
}
