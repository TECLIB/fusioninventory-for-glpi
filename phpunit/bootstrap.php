<?php

define ('TU_USER', '_test_fi');
if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('./glpi'));
   define('FUSINV_ROOT', GLPI_ROOT . DIRECTORY_SEPARATOR . '/plugins/fusioninventory');
   set_include_path(
      get_include_path() . PATH_SEPARATOR .
      GLPI_ROOT . PATH_SEPARATOR .
      GLPI_ROOT . "/plugins/fusioninventory/phpunit/"
   );
}

global $CFG_GLPI;

require_once GLPI_ROOT . '/inc/includes.php';
set_error_handler(['Toolbox', 'userErrorHandlerNormal']);

include_once("Common_TestCase.php");
include_once("RestoreDatabase_TestCase.php");
include_once("LogTest.php");
include_once("commonfunction.php");
