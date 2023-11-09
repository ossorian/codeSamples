<?php

namespace Ac\Pm\SpreadSheet;

interface ExportableInterface
{
    public static function export(array $data);
}