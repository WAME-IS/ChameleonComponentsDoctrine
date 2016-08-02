<?php

namespace Wame\ChameleonComponents\Drivers\DoctrineRepository;

use Nette\InvalidArgumentException;
use stdClass;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\ChameleonComponentsDoctrine\Registers\Types\IRelation;
use Wame\Core\Registers\IRegister;

class RelationsRegister implements IRegister
{

    /** @var array */
    private $relations;

    /** @var array */
    private $relationsNames;

    public function __construct()
    {
        
    }

    /**
     * Register service into register.
     * 
     * @param object $relation
     * @param string $name
     */
    public function add($relation, $name = null)
    {
        if (!$relation instanceof IRelation) {
            throw new InvalidArgumentException("Invalid type, has to be IRelation.");
        }

        $sd = $this->getServiceDefinition($relation);

        if ($sd->name) {
            throw new InvalidArgumentException("Relation for types {$relation->getFrom()->getType()}({$relation->getFrom()->isList()}) and {$relation->getTo()->getType()}({$relation->getTo()->isList()}) is already defined.");
        }

        if (!$name) {
            $name = get_class($relation);
        }

        $sd->name = $name;
        $sd->relation = $relation;

        $this->relationsNames[$name] = $relation;
    }

    /**
     * Remove service from register.
     * 
     * @param object|string $relation Service or name
     */
    public function remove($relation)
    {
        if (is_string($relation)) {
            $relation = array_search($relation, $this->relationsNames);
        }

        $sd = $this->getServiceDefinition($relation);
        $name = $sd->name;
        $sd->name = null;
        $sd->relation = null;
        unset($this->relationsNames[$name]);
    }

    /**
     * Get all registred services
     * 
     * @return array
     */
    public function getAll()
    {
        return $this->relationsNames;
    }

    /**
     * Get service by name
     * 
     * @param string $name
     * @return object Service
     */
    public function getByName($name)
    {
        if (isset($this->relationsNames[$name])) {
            return $this->relationsNames[$name];
        }
    }

    /**
     * Get relation by given types
     * 
     * @param DataDefinitionTarget $from
     * @param DataDefinitionTarget $to
     * @return IRelation
     */
    public function getByTarget(DataDefinitionTarget $from, DataDefinitionTarget $to)
    {
        $sd = $this->getServiceDefinition($from, $to);
        return $sd->relation;
    }

    /**
     * 
     * @param DataDefinitionTarget $from
     * @param DataDefinitionTarget $to
     * @return stdClass
     */
    private function getServiceDefinition(DataDefinitionTarget $from, DataDefinitionTarget $to)
    {
        $arr = $this->relations;

        if (!array_key_exists($from->isList(), $arr)) {
            $arr[$from->isList()] = [];
        }
        $arr = $arr[$from->isList()];

        if (!array_key_exists($to->isList(), $arr)) {
            $arr[$to->isList()] = [];
        }
        $arr = $arr[$to->isList()];

        if (!array_key_exists($from->getType(), $arr)) {
            $arr[$from->getType()] = [];
        }
        $arr = $arr[$from->getType()];

        if (!array_key_exists($to->getType(), $arr)) {
            $arr[$to->getType()] = new stdClass();
        }
        $sd = $arr[$to->getType()];

        return $sd;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->relationsNames);
    }

    public function offsetExists($offset)
    {
        return isset($this->relationsNames[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->relationsNames[$offset];
    }

    public function offsetSet($name, $relation)
    {
        $this->add($relation, $name);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
