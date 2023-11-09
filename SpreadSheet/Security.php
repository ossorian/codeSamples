<?php

namespace Ac\Pm\SpreadSheet;

final class Security
{
    private const SALT = 'Correct_sheet_only';
    
    public static function checkProperRequest(string $sheetName, string $checkString): bool
    {
        return $checkString === self::getCheckString($sheetName);
    }
    
    public static function getCheckString(string $sheetName): string
    {
        return md5(self::SALT . $sheetName);
    }
}