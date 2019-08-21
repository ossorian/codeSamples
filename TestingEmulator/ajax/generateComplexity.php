<?php
require (dirname(dirname(__FILE__)).'/options.php');
$_SESSION["COMPLEX_FROM"] = intval($_GET['min']);
$_SESSION["COMPLEX_TO"] = intval($_GET['max']);
$DB->generateComplexity();
echo json_encode($DB->getComplexValues());