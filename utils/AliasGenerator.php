<?php

namespace Wame\ChameleonComponentsDoctrine\Utils;

class AliasGenerator
{

    /** @var array */
    private $counters = [];

    /**
     * 
     * @param \Kdyby\Doctrine\QueryBuilder $qb
     */
    public function __construct($qb)
    {
        foreach ($qb->getAllAliases() as $alias) {
            if (!isset($this->counters[$alias])) {
                $this->counters[$alias] = 0;
            } else {
                $this->counters[$alias] ++;
            }
        }
    }

    public function getAlias($string)
    {
        $pos = strrpos($string, "\\");
        if ($pos) {
            $string = substr($string, $pos + 1);
        }
        $char = lcfirst($string[0]);

        if (!isset($this->counters[$char])) {
            $this->counters[$char] = 0;
            return $char;
        } else {
            $this->counters[$char] ++;
            return $char . $this->counters[$char];
        }
    }
}
