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
    private $fromField;

    /** @var string */
    private $toField;

    public function __construct(DataDefinitionTarget $from, $fromField, DataDefinitionTarget $to, $toField)
    {
        $this->from = $from;
        $this->to = $to;
        $this->fromField = $fromField;
        $this->toField = $toField;
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
    public function process(QueryBuilder $qb, $from, $to)
    {
        $type = $this->to->getType();

        $fromAlias = $qb->getRootAliases()[0];
        $toAlias = $this->generateAlias($type);

        $qb->innerJoin($type, $toAlias);
        $qb->where($fromAlias . "." . $this->fromField . ' = ' . $toAlias . "." . $this->toField);
    }
    
    /**
     * @param BaseEntity[] $result
     * @param DataSpace $from
     * @param DataSpace $to
     */
    public function postProcess(&$result, $from, $to)
    {
        
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

    /**
     * @param mixed $hint
     * @return boolean
     */
    public function matchHint($hint)
    {
        if(is_string($hint)) {
            if($hint == $this->fromField) {
                return $hint;
            }
        }
    }
}
