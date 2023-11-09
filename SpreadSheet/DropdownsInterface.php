<?php

namespace Ac\Pm\SpreadSheet;

interface DropdownsInterface
{
    public function getForField(string $fieldCode): ?array;
}