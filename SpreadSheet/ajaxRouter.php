<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

/**
 * Здесь лучше ничего не менять!
 */
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$action = $request->getPost('action');
$sheetName = $request->getPost('sheet');
$checkString = $request->getPost('check');

if (!$request->isAjaxRequest()
    || !check_bitrix_sessid()
    || empty($action) || empty($sheetName) || empty($checkString)
    || !\Ac\Pm\SpreadSheet\Security::checkProperRequest($sheetName, $checkString) //Проверка, что сохранение будет происходить в тот же клас, что и редактирование
    || !(is_a($sheetName, '\Ac\Pm\SpreadSheet\MainTable',true))
    || !class_exists($sheetName)
    || !method_exists(\Ac\Pm\SpreadSheet\AjaxActions::class, $action)
) {
    die;
}

$data = $request->getPost('data');

if (($result = \Ac\Pm\SpreadSheet\AjaxActions::$action($sheetName, $data)) !== false) {
    $resultData = [
        'message' => 'Данные успешно сохранены'
    ];
    if (is_array($result)) {
        $resultData = array_merge($resultData, $result);
    }
} else {
    $resultData = [
        'error' => \Ac\Pm\SpreadSheet\AjaxActions::$error ?: 'Ошибка сохранения данных',
        'errorData' => \Ac\Pm\SpreadSheet\AjaxActions::$errorData
    ];
}
echo json_encode($resultData, JSON_UNESCAPED_UNICODE);
