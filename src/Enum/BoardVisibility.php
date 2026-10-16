<?php

namespace App\Enum;

enum BoardVisibility: string
{
    case Public = 'public';
    case Friends = 'friends';
    case Private = 'private';
}
