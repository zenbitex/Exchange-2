<?php

/**
 * Add RSS Feed
 *
 * This actions permit to admin to add rss feed to krypto
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../../config/config.settings.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/vendor/autoload.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";

// Load app modules
$App = new App(true);
$App->_loadModulesControllers();

try {

    // Check loggin & permission
    $User = new User();
    if (!$User->_isLogged()) {
        throw new Exception("Your are not logged", 1);
    }
    if (!$User->_isAdmin()) {
        throw new Exception("Error : Permission denied", 1);
    }

    if($App->_isDemoMode()) throw new Exception("App currently in demo mode", 1);

    // Check data available
    if (empty($_POST) || empty($_POST['kr-additionalpage-name']) || empty($_POST['kr-additionalpage-url'])) {
        throw new Exception("Error : Args not valid", 1);
    }
    if (!filter_var($_POST['kr-additionalpage-url'], FILTER_VALIDATE_URL)) {
        throw new Exception("Error : URL not valid", 1);
    }

    $App->_addAdditionalPage($_POST['kr-additionalpage-name'], $_POST['kr-additionalpage-url'], $_POST['kr-additionalpage-icon'], $_POST['kr-additionalpage-iconsvg']);

    // Return success message
    die(json_encode([
    'error' => 0,
    'msg' => 'Done',
    'title' => 'Success'
  ]));
} catch (\Exception $e) { // If throw exception, return error message
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}
