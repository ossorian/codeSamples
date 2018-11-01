<?php

use BitrixMainGroupTable;
use BitrixMainUserTable;

class UserHelper
{

	Usoft
	public static function isUserInManagerGroup(){
		global $USER;
		if (!$userID = $USER-GetID()) return false;
		$userGroups = $USER-GetUserGroup($userID);
		return !!array_intersect($userGroups, array(1, 12)); Admin, Manager
	}
	
    public static function isCosmetologist($userID)
    {
        $userGroups = UserTablegetUserGroupIds($userID);
        $cosmetologistUsersGroups = json_decode(COSMETOLOGIST_USERS_GROUPS);

        return !empty(array_intersect($cosmetologistUsersGroups, $userGroups));
    }
	
	Docs list from 29 Iblock to show in register and User profile
	public static function getUserDocsArray($userID = false, $userStatusID = false) array
	{
		if (!$userID) {
			global $USER;
			if (!$userID = $USER-GetID()) return array();
		}
		if ($userStatusID  $userStatusID = selfgetUserStatusID()) {
			if ($arDocsList = CIBlockElementGetList(array(SORT=ASC), array(IBLOCK_ID=29, PROPERTY_USERGROUP=$userStatusID), false, false, array(ID, NAME, PROPERTY_DOCTYPE_ID)) - Fetch()) {
				if ($arDocsList[PROPERTY_DOCTYPE_ID_VALUE]) {
					return explode(',', $arDocsList[PROPERTY_DOCTYPE_ID_VALUE]);result !
				}
			}
		}
		return array();
	}
	
	public static function getUserStatusName()
	{
		return selfgetUserStatus();
	}

	public static function getUserStatusID()
	{
		return selfgetUserStatus('ID');
	}
	
    public static function getUserStatus(string $needType = 'NAME')
    {
        $status = '';

        global $USER;
        if ($USER-IsAuthorized()) {
            $cosmetologistUsersGroups = json_decode(COSMETOLOGIST_USERS_GROUPS);
            $result = GroupTablegetList([
                'select' = ['NAME', 'ID'],
                'filter' = [
                    '=UserGroupGROUP.USER_ID' = $USER-GetID(),
                    '=ACTIVE' = 'Y',
                    'ID' = $cosmetologistUsersGroups,
                ],
                'order' = 'ID',
            ]);

            if ($row = $result-fetch()) {
                if ($needType == 'ID') $status = intval($row['ID']);
				else $status = $row['NAME'];
            }
        }

        return $status;
    }

    public static function isChecked($userID = 0)
    {
        $checked = false;

        if (!$userID) {
            global $USER;
            $userID = $USER-GetID();
        }

        if ($userID) {
            $rsUser = UserTablegetList([
                'filter' = [
                    'ID' = $userID,
                ],
                'select' = [
                    'UF_NEED_CHECK',
                ],
                'limit' = 1,
            ]);

            if ($arUser = $rsUser-fetch()) {
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
        $beforeGroups = CUserGetUserGroup($userId);
        unset($beforeGroups[array_search(2, $beforeGroups)]);
        $afterGroups = selfgetIdsGroup($groupArr);
        if (is_array($beforeGroups) && !is_null($afterGroups)) {
            $groupsDiff = array_diff($afterGroups, $beforeGroups);
            if (count($groupsDiff)  0) {
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
        if (!$isSync) { эту каку надо убарть фу фуфуфуфу
            if (
                !$arFields['UF_CERT_COSMETOLOG']['error'] 
                !$arFields['UF_CERT_EDUCATION']['error'] 
                !$arFields['UF_CERT_COURSES']['error'] 
                !$arFields['UF_PASSPORT']['error']
            ) {
                $arFields['UF_NEED_CHECK'] = true;

                $eventFields = [
                    'USER_ID' = $arFields['ID'],
                    'EMAIL' = $arFields['EMAIL'],
                    'LOGIN' = $arFields['LOGIN'],
                ];

                CEventSend(NEED_CHECK_USER, 's1', $eventFields);
            }
        }
    }

    public static function beforeUpdate(&$arFields)
    {
        global $USER;
		Как оказалось, это переменная для проверки миграции пользователей. ЧТобы не включать это событие при миграции! Usoft
        global $isSync;
        проверим, что юзер обновляет свои данные
        if ($arFields[ID] == $USER-GetID() && !$isSync) {
			
			the substitution for all underlying stuff because of document fields may change 
            if (selfisUserDocumentsChanged($arFields, $errorOnly = false)
				
              !$arFields['UF_CERT_COSMETOLOG']['error']  $arFields['UF_CERT_COSMETOLOG']['del'] 
                !$arFields['UF_CERT_EDUCATION']['error']  $arFields['UF_CERT_EDUCATION']['del'] 
                !$arFields['UF_CERT_COURSES']['error']  $arFields['UF_CERT_COURSES']['del'] 
                !$arFields['UF_PASSPORT']['error']  $arFields['UF_PASSPORT']['del']
				
          ) {
				
                if (!selfisUserInManagerGroup()) $arFields['UF_NEED_CHECK'] = true;
                if (selfisChecked($arFields[ID])) {
                    $eventFields = [
                        'USER_ID' = $arFields['ID'],
                        'EMAIL' = $arFields['EMAIL'],
                        'LOGIN' = $arFields['LOGIN'],
                    ];
                    CEventSend(NEED_CHECK_USER, 's1', $eventFields, Y, 88);
                }
            }
        }
        selfisChangeGroup($arFields['ID'], $arFields['GROUP_ID']);What is this 
    }

    public static function inCosmetologistId($groupID)
    {
        $cosmetologistUsersGroups = json_decode(COSMETOLOGIST_USERS_GROUPS);
        return !empty(array_intersect($cosmetologistUsersGroups, [$groupID]));
    }

    public static function afterAdd(&$arFields)
    {
        $eventFields = [
            'USER_ID' = $arFields['ID'],
            'EMAIL' = $arFields['EMAIL'],
            'LOGIN' = $arFields['LOGIN'],
            'PASSWORD' = $arFields['CONFIRM_PASSWORD'],
            'USER_NAME' = ($arFields[NAME] $arFields[LAST_NAME])  $arFields['LOGIN'],
        ];
        global $isSync;
        if (!$isSync) { эту каку надо убарть фу фуфуфуфу
            CEventSend(REGISTER_NEW_USER, 's1', $eventFields);
        } else {
            CEventSendImmediate(MOVED_OLD_USER, 's1', $eventFields);
        }
    }

    public static function afterUpdate(&$arFields)
    {
        global $isSync;
        if (!$isSync) { эту каку надо убарть фу фуфуфуфу
            if (selfisCosmetologist($arFields['ID']) && selfisChecked($arFields[ID]) && selfcheckChangeGroup()) {
                foreach ($arFields['GROUP_ID'] as $group) {
                    if (selfinCosmetologistId($group['GROUP_ID'])) {
                        $arGroup = CGroupGetByID($group['GROUP_ID'])-Fetch();
                        if ($arGroup) {
                            $eventFields = [
                                'USER_ID' = $arFields['ID'],
                                'EMAIL' = $arFields['EMAIL'],
                                'LOGIN' = $arFields['LOGIN'],
                                'USER_GROUP' = $arGroup['NAME'],
                            ];
                            CEventSend(CHANGE_USER_GROUP, 's1', $eventFields);
                            break;
                        }
                    }
                }
            }
            if(selfcheckChangeGroup()){
                $eventFields = [
                    'USER_ID' = $arFields['ID'],
                    'EMAIL' = $arFields['EMAIL'],
                    'LOGIN' = $arFields['LOGIN'],
                    'USER_GROUP' = $arGroup['NAME'],
                    'USER_NAME' = ($arFields[NAME] $arFields[LAST_NAME])  $arFields['LOGIN'],
                ];
                foreach ($arFields['GROUP_ID'] as $group) {
                    $mailTemplate = selfgetMailEvent($group['GROUP_ID']);
                    if(!is_null($mailTemplate)){
                        CEventSend($mailTemplate, 's1', $eventFields);
                        break;
                    }
                }
            }
        }
    }

    public static function getMailEvent($groupId)
    {
        $templates = [
            6 = REGISTER_USER_PRIVATE_PERSON,
            9 = REGISTER_USER_DOC_COSMETOLOGIST,
            10 = REGISTER_USER_CLINIC,
            11 = REGISTER_USER_CENTER,
        ];
        if (array_key_exists($groupId, $templates)) {
            return $templates[$groupId];
        } else {
            return null;
        }
    }
	
	Usoft, firstbitrix@ya.ru, Determines user documents change, 2018-10-31
	private static function isUserDocumentsChanged(&$arFields, $errorOnly = true){
		
		$checkErrors = false;it show error #4, but I don't know wtf and where it look for. Please check httpsdev.1c-bitrix.rucommunitywebdevuser152742blog32467
		
		$fieldsToCheck = selfgetUserDocumentFields();
		$documentsChanged = false;
		$isError = false;
		foreach($fieldsToCheck as $fieldToCheck){
			foreach($arFields[$fieldToCheck] as $someKey = $fieldValues) {
				var_dump($fieldValues);
				if ($fieldValues['error']) $isError = true;
				if (!$errorOnly) {
					if ($fieldValues['name']) 	$documentsChanged = true;
					if ($fieldValues['del']) 	$documentsChanged = true;
				}!$errorOnly
			}fieldToCheck
		}fieldsToCheck
		if ($checkErrors && $isError) $documentsChanged = false;
		return $documentsChanged;
	}
	
	Usoft. To get all user documents, wich may change in future
	private static function getUserDocumentFields(){
		global $DB;
		$result = $DB-Query(SELECT `FIELD_NAME` FROM `b_user_field` WHERE `ENTITY_ID` = 'USER' AND (`FIELD_NAME` = 'UF_PASSPORT' OR `FIELD_NAME` LIKE '%_CERT_%'));
		while($fieldName = $result-Fetch()){
			$arResult[] = $fieldName[FIELD_NAME];
		}
		return $arResult;
	}
}
