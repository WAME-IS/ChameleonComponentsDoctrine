<?php

namespace Wame\ChameleonComponentsDoctrine\Utils;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Nette\Reflection\ClassType;

class MapExpressionVisitor extends ExpressionVisitor
{

    /** @var callable */
    private $fieldCallback;

    /** @var callable */
    private $valueCallback;

    public function __construct($fieldCallback = null, $valueCallback = null)
    {
        $this->fieldCallback = $fieldCallback;
        $this->valueCallback = $valueCallback;
    }

    function setFieldCallback(callable $fieldCallback)
    {
        $this->fieldCallback = $fieldCallback;
    }

    function setValueCallback(callable $valueCallback)
    {
        $this->valueCallback = $valueCallback;
    }

    public function walkComparison(Comparison $comparison)
    {

        if ($this->fieldCallback) {
            $refl = ClassType::from(Comparison::class)->getProperty('field');
            $refl->setAccessible(true);
            $refl->setValue($comparison, call_user_func($this->fieldCallback, $comparison->getField()));
            $refl->setAccessible(false);
        }
    }

    public function walkCompositeExpression(CompositeExpression $expr)
    {
        foreach ($expr->getExpressionList() as $child) {
            $this->dispatch($child);
        }
    }

    public function walkValue(Value $value)
    {
        if ($this->valueCallback) {
            $refl = ClassType::from(Value::class)->getProperty('value');
            $refl->setAccessible(true);
            $refl->setValue($value, call_user_func($this->valueCallback, $value->getValue()));
            $refl->setAccessible(false);
        }
    }
}
