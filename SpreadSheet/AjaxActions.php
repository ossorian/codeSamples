<?php

namespace Ac\Pm\SpreadSheet;

/**
 * Сюда можно прописывать только общие действия, использующие сторонние обработчики, либо воодить дополнительные классы таблиц, которые будут сами обрабатывать действия
 * Class AjaxActions
 * @package Ac\Pm\SpreadSheet
 */
final class AjaxActions
{
    public static $error = '';
    public static ?array $errorData = null;
    
    public static function saveData(string $sheetName, $data)
    {
        if (!is_array($data)) {
            self::$error = 'Не правильно переданные данные';
            return false;
        }
    
        $sendBackData = [];
        foreach ($data as $rowKey => $datum) {
            
            if (!is_numeric($rowKey)) {
                continue;
            }
            
            //Изменение данных, в зависимости от таблицы, возвращает данные, которые нужно отправить на фронт обратно
            if (method_exists($sheetName, 'convertDataBeforeSave')) {
                $mergeSendDatum = $sheetName::convertDataBeforeSave($datum);
            } else {
                $mergeSendDatum = [];
            }
            
            //В основном для проверки обязательных полей, возвращает пустоту или ошибку. Возможно, нужно передавать текущее изменяемое поле, но пока такой необходимости нет.
            if (method_exists($sheetName, 'checkBeforeSave') && ($errorMessage = $sheetName::checkBeforeSave($datum))) {
                self::$error = $errorMessage;
                self::$errorData = ['errorRow' => $rowKey];
                return false;
            }
            
            if ($datumId = $datum['ID']) {
                unset($datum['ID']);
                $result = $sheetName::update($datumId, $datum);
                if (!$result->isSuccess()) {
                    self::$error = implode(', ', $result->getErrorMessages());
                    return false;
                }
            } else {
                $result = $sheetName::add($datum);
                if (!$result->isSuccess()) {
                    self::$error = $result->getErrors();
                    return false;
                }
                $sendBackData[$rowKey]['ID'] = $result->getID();
                $sendBackData[$rowKey] = array_merge($sendBackData[$rowKey], $mergeSendDatum);
            }
        }
        $sendResult = [
            'newData' => $sendBackData
        ];
        return $sendResult;
    }
    
    public static function deleteRow(string $sheetName, $data) {
        if ($id = IntVal($data)) {
    
            if (method_exists($sheetName, 'checkBeforeDelete') && ($errorMessage = $sheetName::checkBeforeDelete($id))) {
                self::$error = $errorMessage;
                return false;
            }
            
            $result =$sheetName::delete($id);
            if (!$result->isSuccess()) {
                self::$error = "Не удалось удалить элемент $id";
                return false;
            }
            return [];
        } else {
            self::$error = "Не верно передан элемент для удаления $id";
            return false;
        }
    }
    
}