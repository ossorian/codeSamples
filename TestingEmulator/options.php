<?php
//No need to make autoload for small project.
$path = dirname(__FILE__).'/classes/';
require ($path.'DataBaseController.php');
$DB = new DataBaseController;

require ($path.'QuestionGenerator.php');
require ($path.'QuestionGeneratorDB.php');
require ($path.'EmulatorCalculator.php');

session_start();
require ($path.'UserInterface.php');
UserInterface::initVars();
