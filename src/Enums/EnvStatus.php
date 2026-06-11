<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Enums;

enum EnvStatus: string
{
    case Keep = 'keep';
    case Modified = 'modified';
    case Added = 'added';
    case Removed = 'removed';
}
