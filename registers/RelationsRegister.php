<?php

namespace Wame\ChameleonComponentsDoctrine\Registers;

use ArrayIterator;
use Nette\InvalidArgumentException;
use stdClass;
use Wame\ChameleonComponents\Definition\DataDefinitionTarget;
use Wame\ChameleonComponentsDoctrine\Registers\Types\IRelation;
use Wame\Core\Registers\IRegister;

class RelationsRegister implements IRegister
{

    /** @var array */
    private $relations = [];

    /** @var array */
    private $relationsNames = [];

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

        $this->addRelation($relation->getFrom(), $relation->getTo(), $relation);

        if (!$name) {
            $name = get_class($relation);
        }

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

        $relations = $this->getServiceDefinitions($relation->getFrom(), $relation->getTo());
        if ($relations) {
            $key1 = $this->getListsKey($relation->getFrom(), $relation->getTo());
            $key2 = array_search($relation, $relations);
            unset($this->relations[$key1][$relation->getFrom()->getType()][$relation->getTo()->getType()][$key2]);
            $key3 = array_search($relation, $this->relationsNames);
            unset($this->relationsNames[$key3]);
        }
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
    public function getByTarget(DataDefinitionTarget $from, DataDefinitionTarget $to, $hint = null)
    {
        if ($hint && $hint instanceof IRelation) {
            return $hint;
        }

        $relations = $this->getServiceDefinitions($from, $to);

        if ($relations) {
            if (count($relations) == 1) {
                return $relations[0];
            } else {

                if (!$hint) {
                    $e = new InvalidArgumentException("Control has multiple possible relations and no hint specified.");
                    $e->relations = $relations;
                    throw $e;
                }

                return $this->chooseRelationByHint($relations, $hint);
            }
        }
    }

    /**
     * @param IRelation[] $relations
     * @param string $hint
     */
    private function chooseRelationByHint($relations, $hint)
    {
        foreach($relations as $relation) {
            if($relation->matchHint($hint)) {
                return $relation;
            }
        }
    }

    /**
     * @param DataDefinitionTarget $from
     * @param DataDefinitionTarget $to
     * @return stdClass[]
     */
    private function getServiceDefinitions(DataDefinitionTarget $from, DataDefinitionTarget $to)
    {
        $arr = $this->relations;

        $key1 = 0;
        $key1 += $from->isList() ? 1 : 0;
        $key1 += $to->isList() ? 2 : 0;

        if (!array_key_exists($key1, $arr)) {
            return null;
        }
        $arr = $arr[$key1];

        if (!array_key_exists($from->getType(), $arr)) {
            return null;
        }
        $arr = $arr[$from->getType()];

        if (!array_key_exists($to->getType(), $arr)) {
            return null;
        }

        return $arr[$to->getType()];
    }

    /**
     * 
     * @param DataDefinitionTarget $from
     * @param DataDefinitionTarget $to
     * @return stdClass
     */
    private function addRelation(DataDefinitionTarget $from, DataDefinitionTarget $to, $relation)
    {
        $arr = $this->relations;

        $key1 = 0;
        $key1 += $from->isList() ? 1 : 0;
        $key1 += $to->isList() ? 2 : 0;

        if (!array_key_exists($key1, $arr)) {
            $arr[$key1] = $this->relations[$key1] = [];
        }
        $arr = $arr[$key1];

        if (!array_key_exists($from->getType(), $arr)) {
            $arr[$from->getType()] = $this->relations[$key1][$from->getType()] = [];
        }
        $arr = $arr[$from->getType()];

        if (!array_key_exists($to->getType(), $arr)) {
            $arr[$to->getType()] = $this->relations[$key1][$from->getType()][$to->getType()] = [];
        }

        $this->relations[$key1][$from->getType()][$to->getType()][] = $relation;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->relationsNames);
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
