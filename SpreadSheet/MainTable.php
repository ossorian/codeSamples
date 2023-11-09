<?php

namespace Ac\Pm\SpreadSheet;

use Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields;
use RuntimeException;

abstract class MainTable extends DataManager
{
    
    public const TABLENAME = '';
    public const HIDDEN_FIELDS = ['ID'];
    public const URGENT_SEND_FIELDS = [];
    protected const NESTED_HEADERS = [];
    
    public static function getTableName()
    {
        if (empty(static::TABLENAME)) {
            throw new RuntimeException('Не указано имя таблицы в классе ' . get_class(static::class));
        }
        return static::TABLENAME;
    }
    
    public static function getMap(): array
    {
        $map[] = new Fields\IntegerField(
            'ID',
            [
                'primary' => true,
                'autocomplete' => true,
            ]
        );
        $fields = static::getFields();
        $nontextFieldTypes = static::getNontextFieldTypes();
        foreach ($fields as $code => $title) {
            $fieldType = $nontextFieldTypes[$code] ?: 'TextField';
            $fieldObjectName = '\\Bitrix\\Main\\ORM\\Fields\\' . $fieldType;
            $map[] = new $fieldObjectName($code, ['title' => $title]);
        }
        return $map;
    }
    
    /**
     * Поля, заполняемые автоматически при создании новой строки.
     * См. пример \Ac\Pm\Spg\Tables\CheckUrgentFieldsBeforeSendTrait::getPrefilledFields
     * @return array
     */
    public static function getPrefilledFields(): array
    {
        return [];
    }
    
    public static function getUrgentFields(): array
    {
        return [];
    }
    
    
    public function getNestedHeaders(): array
    {
        $arNestedHeaders = [];
        $total = 0;
        
        foreach (static::NESTED_HEADERS as $header) {
            $title = $header['title'];
            $colspan = $header['colspan'];
            $total += $colspan;
            if ($colspan === '#END') {
                $totalFieldsAmount = sizeof(static::getMap());
                $hiddenAmount = 0;
                if (method_exists(new static, 'initRightsModel')) {
                    static::initRightsModel();
                    $hiddenAmount = sizeof((static::$rightsModel)->getHiddenColumns());
                }
                $fieldAmountLeft = $totalFieldsAmount - $hiddenAmount - $total;
                $colspan = $fieldAmountLeft;
            }
            $arNestedHeaders[] = ['title' => $title, 'colspan' => $colspan];
        }
        return $arNestedHeaders;
    }
    
    abstract protected static function getFields(): array;
    abstract protected static function getNontextFieldTypes(): array;
    
}