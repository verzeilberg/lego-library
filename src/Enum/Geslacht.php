<?php

namespace App\Enum;

enum Geslacht: string
{
    case Man = 'man';
    case Vrouw = 'vrouw';
    case GeslachtNeutraal = 'geslacht_neutraal';
}
