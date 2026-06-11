<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Enums;

enum Actions: string
{
    case Help = 'help';
    case List = 'list';
    case Set = 'set';
    case Delete = 'delete';
    case Reset = 'reset';
    case Finish = 'finish';
}
