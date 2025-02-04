<?php

namespace ResponseFilterBundle\Enum;

enum ResponseFilterRuleTypeEnum: int
{
    case REMOVE = 1;
    case SET = 2;
    case STR_REPLACE = 3;
}
