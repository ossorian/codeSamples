<?php
function customAutoload($className) {
    if (!CModule::RequireAutoloadClass($className)) {

		$mainPath = $_SERVER["DOCUMENT_ROOT"]."/local/php_interface/classes/";
		$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        $path = $mainPath . ucfirst($className) . '.php';

        if ((file_exists($path)) {
            require_once $path;
            return true;
        }
        return false;
    }
    return true;
}
spl_autoload_register('customAutoload', true);