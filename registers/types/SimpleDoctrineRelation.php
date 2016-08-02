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
    public function apply(QueryBuilder $qb)
    {
        $type = $this->to->getType();

        dump($qb->getRootAliases());

        $fromAlias = $qb->getRootAliases()[0];
        $toAlias = $this->generateAlias($type);

        $qb->innerJoin($type, $toAlias);
        $qb->where($fromAlias . "." . $this->fromField . ' = ' . $toAlias . "." . $this->toField);
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
