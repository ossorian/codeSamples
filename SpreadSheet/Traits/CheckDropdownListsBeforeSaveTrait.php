<?php

namespace Ac\Pm\SpreadSheet\Traits;

trait CheckDropdownListsBeforeSaveTrait {
    
    public static function checkDropdowns(\Ac\Pm\SpreadSheet\DropdownsInterface $dropdowns, \Ac\Pm\SpreadSheet\MainTable $table, array $data): string
    {
        $urgentFields = $table::getUrgentFields();
        foreach ($data as $fieldCode => $value) {
            
            //Значение поля не обязательно. Поэтому если его вообще нет, то пропускаем.
            if (($value === '') && !in_array($fieldCode, $urgentFields)) {
                continue;
            }
            
            if ((($list = $dropdowns->getForField($fieldCode)) !== null) && !in_array($value, $list)) {
                return 'Заполнение поля "' . $table::TABLEFIELDS[$fieldCode] .'" не соответствует допустимым значениям';
            }
        }
        return '';
    }
}