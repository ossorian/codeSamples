<?php
use Bitrix\Main\GroupTable;
use Bitrix\Main\UserTable;

/* Данный файл взят с рабочего сайта в качестве класса обработчика событий, связанных с изменениями данных о пользователях.
Первые два метода - рефакторинг, третий - пятые методы мои, остальные осталось как наследие от прошлых разработчиков.

Внимание! В данном файле нарушено правило отображения сперва публичных, затем приватных методов, 
а также правило комментирования только на английском языке. Это сделано для большей наглядности.
Как он выглядел изначально всегда можно посмотреть в истории изменений.
 */
 
class UserHelper
{

	//Здесь был небольшой рефакторинг чужого кода
    public static function getUserStatus(string $needType = 'NAME')
    {
        $status = '';

        global $USER;
        if ($USER->IsAuthorized()) {
            $cosmetologistUsersGroups = json_decode(COSMETOLOGIST_USERS_GROUPS);
            $result = GroupTable::getList([
                'select' => ['NAME', 'ID'],
                'filter' => [
                    '=UserGroup:GROUP.USER_ID' => $USER->GetID(),
                    '=ACTIVE' => 'Y',
                    'ID' => $cosmetologistUsersGroups,
                ],
                'order' => 'ID',
            ]);

			//Изменение коснулось вот этого блока, ранее возвращался только NAME, чтобы не создавать доп метод для возврата ID
            if ($row = $result->fetch()) {
                if ($needType == 'ID') $status = intval($row['ID']);
				else $status = $row['NAME'];
            }
        }

        return $status;
    }

	//Рефакторинг
    public static function beforeUpdate(&$arFields)
    {
        global $USER;
        global $isSync;//Чужой код! Я таких вещейн не допускаю, но убрать её сейчас действительно сложно.
		
        if ($arFields["ID"] == $USER->GetID() && !$isSync /* Чужой код ! */) {
			
/* 			Вот это замена моя. Вместо множества условий , закомментированных ниже, которые, кстати, работают неверно
			вставляется единственный почти универсальный метод, позволяющий учесть расширение пользовательских полей на будущее.
 */			
            if (self::isUserDocumentsChanged($arFields, $errorOnly = false) 
				
/*              !$arFields['UF_CERT_COSMETOLOG']['error'] || $arFields['UF_CERT_COSMETOLOG']['del'] ||
                !$arFields['UF_CERT_EDUCATION']['error'] || $arFields['UF_CERT_EDUCATION']['del'] ||
                !$arFields['UF_CERT_COURSES']['error'] || $arFields['UF_CERT_COURSES']['del'] ||
                !$arFields['UF_PASSPORT']['error'] || $arFields['UF_PASSPORT']['del']
				
 */         ) {
				
                if (!self::isUserInManagerGroup()) $arFields['UF_NEED_CHECK'] = true;
                if (self::isChecked($arFields["ID"])) {
                    $eventFields = [
                        'USER_ID' => $arFields['ID'],
                        'EMAIL' => $arFields['EMAIL'],
                        'LOGIN' => $arFields['LOGIN'],
                    ];
                    CEvent::Send("NEED_CHECK_USER", 's1', $eventFields, "Y", 88);
                }
            }
        }
        self::isChangeGroup($arFields['ID'], $arFields['GROUP_ID']);//What is this ?
    }

	//Полностью мой код
	public static function isUserInManagerGroup()
	{
		global $USER;
		if (!$userID = $USER->GetID()) return false;
		$userGroups = $USER->GetUserGroup($userID);
		return !!array_intersect($userGroups, array(1, 12)); //Admin, Manager groups, should some controller in future.
	}

	//Usoft, firstbitrix@ya.ru, Determines user documents change, 2018-10-31
	private static function isUserDocumentsChanged(&$arFields, $errorOnly = true)
	{
		$checkErrors = false;
		$fieldsToCheck = self::getUserDocumentFields();
		$documentsChanged = false;
		$isError = false;
		
		foreach($fieldsToCheck as $fieldToCheck){
			foreach($arFields[$fieldToCheck] as $someKey => $fieldValues) {
				if ($fieldValues['error']) $isError = true;
				if (!$errorOnly) {
					if ($fieldValues['name']) 	$documentsChanged = true;
					if ($fieldValues['del']) 	$documentsChanged = true;
				}//!$errorOnly
			}//fieldToCheck
		}//fieldsToCheck
		
		if ($checkErrors && $isError) $documentsChanged = false;
		return $documentsChanged;
	}
	
	//Usoft. To get ALL user documents, wich may change in future
	private static function getUserDocumentFields()
	{
		global $DB;
		$result = $DB->Query("SELECT `FIELD_NAME` FROM `b_user_field` WHERE `ENTITY_ID` = 'USER' AND (`FIELD_NAME` = 'UF_PASSPORT' OR `FIELD_NAME` LIKE '%_CERT_%')");
		
		while($fieldName = $result->Fetch()) {
			$arResult[] = $fieldName["FIELD_NAME"];
		}
		return $arResult;
	}	

	// --------------
	//Далее чужой код
	// --------------
	
    public static function isCosmetologist($userID)
    {
        $userGroups = UserTable::getUserGroupIds($userID);
        $cosmetologistUsersGroups = json_decode(COSMETOLOGIST_USERS_GROUPS);

        return !empty(array_intersect($cosmetologistUsersGroups, $userGroups));
    }
	
	//Docs list from 29 Iblock to show it in register and User profile
	public static function getUserDocsArray($userID = false, $userStatusID = false): array
	{
		if (!$userID) {
			global $USER;
			if (!$userID = $USER->GetID()) return array();
		}
		if ($userStatusID || $userStatusID = self::getUserStatusID()) {
			if ($arDocsList = CIBlockElement::GetList(array("SORT"=>"ASC"), array("IBLOCK_ID"=>29, "PROPERTY_USERGROUP"=>$userStatusID), false, false, array("ID", "NAME", "PROPERTY_DOCTYPE_ID")) -> Fetch()) {
				if ($arDocsList["PROPERTY_DOCTYPE_ID_VALUE"]) {
					return explode(',', $arDocsList["PROPERTY_DOCTYPE_ID_VALUE"]);//result !
				}
			}
		}
		return array();
	}
	
	public static function getUserStatusName()
	{
		return self::getUserStatus();
	}

	public static function getUserStatusID()
	{
		return self::getUserStatus('ID');
	}

    public static function isChecked($userID = 0)
    {
        $checked = false;

        if (!$userID) {
            global $USER;
            $userID = $USER->GetID();
        }

        if ($userID) {
            $rsUser = UserTable::getList([
                'filter' => [
                    'ID' => $userID,
                ],
                'select' => [
                    'UF_NEED_CHECK',
                ],
                'limit' => 1,
            ]);

            if ($arUser = $rsUser->fetch()) {
                $checked = !$arUser['UF_NEED_CHECK'];
            }
        }

        return $checked;
    }

    public static function getIdsGroup($groupArr)
    {
        if (is_array($groupArr)) {
            return array_map(function ($item) {
                return $item['GROUP_ID'];
            }, $groupArr);

        }
        return null;
    }
	
    public static function isChangeGroup($userId, $groupArr)
    {
        global $isChangeGroup;
        $isChangeGroup = false;
        $beforeGroups = CUser::GetUserGroup($userId);
        unset($beforeGroups[array_search(2, $beforeGroups)]);
        $afterGroups = self::getIdsGroup($groupArr);
        if (is_array($beforeGroups) && !is_null($afterGroups)) {
            $groupsDiff = array_diff($afterGroups, $beforeGroups);
            if (count($groupsDiff) > 0) {
                $isChangeGroup = true;
            }
        }
    }

    public static function checkChangeGroup()
    {
        global $isChangeGroup;
        return $isChangeGroup;
    }

    public static function beforeAdd(&$arFields)
    {
        global $isSync;
        if (!$isSync) { 
            if (
                !$arFields['UF_CERT_COSMETOLOG']['error'] ||
                !$arFields['UF_CERT_EDUCATION']['error'] ||
                !$arFields['UF_CERT_COURSES']['error'] ||
                !$arFields['UF_PASSPORT']['error']
            ) {
                $arFields['UF_NEED_CHECK'] = true;

                $eventFields = [
                    'USER_ID' => $arFields['ID'],
                    'EMAIL' => $arFields['EMAIL'],
                    'LOGIN' => $arFields['LOGIN'],
                ];

                CEvent::Send("NEED_CHECK_USER", 's1', $eventFields);
            }
        }
    }

    public static function inCosmetologistId($groupID)
    {
        $cosmetologistUsersGroups = json_decode(COSMETOLOGIST_USERS_GROUPS);
        return !empty(array_intersect($cosmetologistUsersGroups, [$groupID]));
    }

    public static function afterAdd(&$arFields)
    {
        $eventFields = [
            'USER_ID' => $arFields['ID'],
            'EMAIL' => $arFields['EMAIL'],
            'LOGIN' => $arFields['LOGIN'],
            'PASSWORD' => $arFields['CONFIRM_PASSWORD'],
            'USER_NAME' => ("$arFields[NAME] $arFields[LAST_NAME]") ?: $arFields['LOGIN'],
        ];
        global $isSync;
        if (!$isSync) { 
            CEvent::Send("REGISTER_NEW_USER", 's1', $eventFields);
        } else {
//            CEvent::SendImmediate("MOVED_OLD_USER", 's1', $eventFields);
        }
    }

    public static function afterUpdate(&$arFields)
    {
        global $isSync;
        if (!$isSync) { 
            if (self::isCosmetologist($arFields['ID']) && self::isChecked($arFields["ID"]) && self::checkChangeGroup()) {
                foreach ($arFields['GROUP_ID'] as $group) {
                    if (self::inCosmetologistId($group['GROUP_ID'])) {
                        $arGroup = CGroup::GetByID($group['GROUP_ID'])->Fetch();
                        if ($arGroup) {
                            $eventFields = [
                                'USER_ID' => $arFields['ID'],
                                'EMAIL' => $arFields['EMAIL'],
                                'LOGIN' => $arFields['LOGIN'],
                                'USER_GROUP' => $arGroup['NAME'],
                            ];
//                            CEvent::Send("CHANGE_USER_GROUP", 's1', $eventFields);
                            break;
                        }
                    }
                }
            }
            if(self::checkChangeGroup()){
                $eventFields = [
                    'USER_ID' => $arFields['ID'],
                    'EMAIL' => $arFields['EMAIL'],
                    'LOGIN' => $arFields['LOGIN'],
                    'USER_GROUP' => $arGroup['NAME'],
                    'USER_NAME' => ("$arFields[NAME] $arFields[LAST_NAME]") ?: $arFields['LOGIN'],
                ];
                foreach ($arFields['GROUP_ID'] as $group) {
                    $mailTemplate = self::getMailEvent($group['GROUP_ID']);
                    if(!is_null($mailTemplate)){
                        CEvent::Send($mailTemplate, 's1', $eventFields);
                        break;
                    }
                }
            }
        }
    }

    public static function getMailEvent($groupId)
    {
        $templates = [
            6 => "REGISTER_USER_PRIVATE_PERSON",
            9 => "REGISTER_USER_DOC_COSMETOLOGIST",
            10 => "REGISTER_USER_CLINIC",
            11 => "REGISTER_USER_CENTER",
        ];
        if (array_key_exists($groupId, $templates)) {
            return $templates[$groupId];
        } else {
            return null;
        }
    }
}