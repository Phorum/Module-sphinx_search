<?php

// Check if we are loaded from the Phorum admin code.
// Direct access to this file is not allowed.
if (! defined("PHORUM_ADMIN")) return;

require_once("./mods/sphinx_search/defaults.php");

// For shorter writing.
$settings =& $GLOBALS["PHORUM"]["mod_sphinx_search"];

if (count($_POST))
{
    // Check for errors in the settings.

    $errors = array();

    $_POST["sphinx_host"] = trim($_POST["sphinx_host"]);
    if (!preg_match('/^[\w\.-]+$/', $_POST["sphinx_host"])) {
        $errors[] = "The Sphinx host field should contain a valid host name.";
    }
    $settings["sphinx_host"] = $_POST["sphinx_host"];

    if (!is_numeric($_POST["sphinx_port"])) {
        $errors[] = "The Sphinx port field should contain a numerical value.";
    }
    $settings["sphinx_port"] = (int) $_POST["sphinx_port"];

    $settings["message_index"]  = trim($_POST["message_index"]);
    $settings["author_index"]   = trim($_POST["author_index"]);

    if (!is_numeric($_POST["max_search_results"])) {
        $errors[] = "The maximum search results field should contain a numerical value.";
    }
    $settings["max_search_results"] = (int) $_POST["max_search_results"];

    if (count($errors)) {
        phorum_admin_error(implode("<br>", $errors));
    } else {
        phorum_db_update_settings(array("mod_sphinx_search" => $settings));
        phorum_admin_okmsg("The settings were successfully saved.");
    }
}

// Create the settings form.
include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Save settings");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "sphinx_search");
$frm->hidden("sp_action", "settings");

$frm->addbreak("Module Settings");

$frm->addsubbreak("Sphinx server configuration");

$row = $frm->addrow("Sphinx server IP address or hostname", $frm->text_box("sphinx_host", $settings["sphinx_host"], 50));

$row = $frm->addrow("Sphinx server port", $frm->text_box("sphinx_port", $settings["sphinx_port"], 10));

$row = $frm->addrow("Message index(es) to use", $frm->text_box("message_index", $settings["message_index"], 50));

$row = $frm->addrow("Author index(es) to use", $frm->text_box("author_index", $settings["author_index"], 50));

$row = $frm->addrow("Maximum number of search results", $frm->text_box("max_search_results", $settings["max_search_results"], 10));

$frm->show();

?>
