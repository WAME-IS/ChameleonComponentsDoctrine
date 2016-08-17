<?php

namespace Wame\ChameleonComponents\Drivers\DoctrineRepository;

use Wame\ChameleonComponentsDoctrine\Registers\Types\IQueryType;
use Wame\Core\Registers\BaseRegister;

class QueryTypesRegister extends BaseRegister
{

    public function __construct()
    {
        parent::__construct(IQueryType::class);
    }
}
