<?php

namespace App\Enums;

enum FilesystemVisibility: string
{
    case PRIVATE = 'private';
    case PUBLIC = 'public';
}
