<?php

namespace App\Enums;

// https://www.asciiart.eu/text-to-ascii-art
// Font: Colossal

enum Letter: string
{
    case W = <<<'LETTER'
    888       888
    888   o   888
    888  d8b  888
    888 d888b 888
    888d88888b888
    88888P Y88888
    8888P   Y8888
    888P     Y888
    LETTER;

    case E = <<<'LETTER'
    8888888888
    888
    888
    8888888
    888
    888
    888
    8888888888
    LETTER;

    case M = <<<'LETTER'
    888b     d888
    8888b   d8888
    88888b.d88888
    888Y88888P888
    888 Y888P 888
    888  Y8P  888
    888   "   888
    888       888
    LETTER;

    case U = <<<'LETTER'
    888     888
    888     888
    888     888
    888     888
    888     888
    888     888
    Y88b. .d88P
     "Y88888P"
    LETTER;

    case S = <<<'LETTER'
     .d8888b.
    d88P  Y88b
    Y88b.
     "Y888b.
        "Y88b.
          "888
    Y88b  d88P
     "Y8888P"
    LETTER;

    case T = <<<'LETTER'
    88888888888
        888
        888
        888
        888
        888
        888
        888
    LETTER;

    case H = <<<'LETTER'
    888    888
    888    888
    888    888
    8888888888
    888    888
    888    888
    888    888
    888    888
    LETTER;

    case I = <<<'LETTER'
    8888888
      888
      888
      888
      888
      888
      888
    8888888
    LETTER;

    case P = <<<'LETTER'
    8888888b.
    888   Y88b
    888    888
    888   d88P
    8888888P"
    888
    888
    888
    LETTER;
}
