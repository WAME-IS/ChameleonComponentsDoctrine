<?php

namespace Wame\ChameleonComponentsDoctrine\Utils;

use Doctrine\Common\Collections\Criteria;

class Utils
{

    /**
     * Prefix cirteria with string prefix
     * 
     * @param Criteria $criteria
     * @param string $prefix
     * @return Criteria
     */
    public static function prefixCriteria($criteria, $prefix)
    {
        $criteria = unserialize(serialize($criteria)); // Deep copy
        
        $visitor = new MapExpressionVisitor(function($field)use($prefix) {
            return $prefix . '.' . $field;
        });
        $visitor->dispatch($criteria->getWhereExpression());

        return $criteria;
    }

    public static function readCriteria($array)
    {
        $criteria = Criteria::create();

        if (isset($array['where'])) {
            $criteria->where(self::readExpression($array['where']));
        }

        if (isset($array['order'])) {
            if (!is_array($array['order'])) {
                throw new InvalidArgumentException("Order has to be array");
            }
            $criteria->orderBy($array['order']);
        }

        if (isset($array['limit'])) {
            if (!is_int($array['limit'])) {
                throw new InvalidArgumentException("Limit has to be int");
            }
            $criteria->setMaxResults($array['limit']);
        }

        if (isset($array['maxResults'])) {
            if (!is_int($array['maxResults'])) {
                throw new InvalidArgumentException("MaxResults has to be int");
            }
            $criteria->setFirstResult($array['maxResults']);
        }

        if (isset($array['firstResult'])) {
            if (!is_int($array['firstResult'])) {
                throw new InvalidArgumentException("FirstResult has to be int");
            }
            $criteria->setFirstResult($array['firstResult']);
        }

        return $criteria;
    }

    public function readExpression($value)
    {
        if (is_array($value)) {
            return array_map(function($v) {
                return self::readExpression($v);
            }, $value);
        }

        foreach (Strings::matchAll($value, '-(.*) (=|<>|<|<=|>|>=|=|IN|NIN|CONTAINS) (.*)-') as $match) {
            return new Comparison($match[1], $match[2], new Value($match[3]));
        }
    }
}
