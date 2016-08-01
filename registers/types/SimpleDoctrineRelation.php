<?php

namespace Wame\ChameleonComponentsDoctrine\Registers\Types;

use Kdyby\Doctrine\QueryBuilder;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\Utils\Strings;

class SimpleDoctrineRelation implements IRelation
{

    /** @var DataDefinitionTarget */
    private $from;

    /** @var DataDefinitionTarget */
    private $to;

    /** @var string */
    private $fieldName;
    
    public function __construct(DataDefinitionTarget $from, DataDefinitionTarget $to, $fieldName)
    {
        $this->from = $from;
        $this->to = $to;
        $this->fieldName = $fieldName;
    }

    /**
     * @return DataDefinitionTarget
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return DataDefinitionTarget
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param QueryBuilder $qb
     */
    public function apply($qb)
    {
        $type = $this->to->getType();
        $alias = $this->generateAlias($type);
        $qb->innerJoin($type, $alias);
        $qb->where(\Doctrine\Common\Collections\Criteria::expr()->eq($alias.".".$this->fieldName, $this->getRelationValue()));
    }
    
    private function getRelationValue()
    {
        // TODO :D
    }

    /**
     * @param string $type
     * @return string
     */
    private function generateAlias($type)
    {
        $alias = Strings::lower(Strings::getClassName($type));
        if (Strings::endsWith($alias, 'entity')) {
            $alias = substr($alias, 0, -6);
        }
        return $alias;
    }
}
