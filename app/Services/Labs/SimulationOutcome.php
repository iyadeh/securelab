<?php

namespace App\Services\Labs;

enum SimulationOutcome: string
{
    case NormalBehavior = 'normal_behavior';
    case VulnerableBehavior = 'vulnerable_behavior';
    case Rejected = 'rejected';
}
