<?php

// if we are running in the webserver, bail out
if (isset($_SERVER["REMOTE_ADDR"])) {
    trigger_error(
        "This script cannot be run from a browser.\n",
        E_USER_ERROR
    );
}

define("phorum_page", "sphinx_install_helper");
define("PHORUM_ADMIN", 1);

// I guess the phorum-directory should be two levels up.
$PHORUM_DIRECTORY = dirname(__FILE__) . "/../../";

// change directory to the main-dir so we can use common.php
if(file_exists($PHORUM_DIRECTORY."/common.php")) {
    chdir($PHORUM_DIRECTORY);
    if (!is_readable("./common.php")) {
        fprintf(STDERR,
            "Unable to read common.php from directory $PHORUM_DIRECTORY\n");
        exit(1);
    }
} else {
    fprintf(STDERR,
        "Unable to find Phorum file \"common.php\".\n" .
        "Please check the \$PHORUM_DIRECTORY in " . basename(__FILE__) ."\n");
    exit(1);
}

require_once("common.php");

// Check the db configuration. We only do Sphinx for MySQL, so check if
// we have MySQL as our database and if it's fully configured.
if (!strstr($PHORUM["DBCONFIG"]["type"], "mysql")) {
    trigger_error(
        "It looks like you database type is not MySQL.\n" .
        "This module only runs with MySQL as the database backend.\n",
        E_USER_ERROR
    );
}
foreach (array("server", "name", "user", "password", "table_prefix") as $fld) {
    if (!isset($PHORUM["DBCONFIG"][$fld])) trigger_error(
        "The configuration field \"$fld\" is missing in your Phorum\n" .
        "database configuration include/db/config.php. Please fix this\n" .
        "and retry running this script.\n",
        E_USER_ERROR
    );
}

print "\nSphinx install helper\n" .
      "---------------------\n\n";

// This array holds the Sphinx configuration variables that we are using.
$options = array
(
    array(
        "DBHOST",
        $PHORUM["DBCONFIG"]["server"],
        "The hostname for connecting to the Phorum database server\n" .
        "\n" .
        "You should not have to change this value, since the default value\n".
        "is the actual value that Phorum is uses for accessing your Phorum\n".
        "database data."
    ),
    array(
        "DBPORT",
        3306,
        "The port at which the Phorum database server is running (default\n" .
        "is \"3306\" and normally you should not have to change that value)."
    ),
    array(
        "DBNAME",
        $PHORUM["DBCONFIG"]["name"],
        "The name of the Phorum database.\n" .
        "\n" .
        "You should not have to change this value, since the default value\n".
        "is the actual value that Phorum is uses for accessing your Phorum\n".
        "database data."
    ),
    array (
        "DBUSER",
        $PHORUM["DBCONFIG"]["user"],
        "The username for accessing the Phorum database.\n" .
        "\n" .
        "You should not have to change this value, since the default value\n".
        "is the actual value that Phorum is uses for accessing your Phorum\n".
        "database data."
    ),
    array (
        "DBPASS",
        $PHORUM["DBCONFIG"]["password"],
        "The password for the Phorum database.\n" .
        "\n" .
        "You should not have to change this value, since the default value\n".
        "is the actual value that Phorum is uses for accessing your Phorum\n".
        "database data."
    ),
    array (
        "DBPREFIX",
        $PHORUM["DBCONFIG"]["table_prefix"],
        "The database prefix for the Phorum database.\n" .
        "\n" .
        "You should not have to change this value, since the default value\n".
        "is the actual value that Phorum is uses for accessing your Phorum\n".
        "database data."
    ),
    array (
        "MESSAGE_TABLE",
        isset($PHORUM["message_table"])
        ? $PHORUM["message_table"] : "",
        "This is the name of the Phorum database table in which the forum\n" .
        "messages are stored.\n" .
        "\n" .
        "You should not have to change this value, since the default value\n".
        "is the actual value that Phorum is uses for accessing your Phorum\n".
        "database data."
    ),
    array (
        "DATAPATH",
        "/var/sphinx",
        "The path to the directory where you will store the Sphinx data\n" .
        "files. This directory should be writable for the user which is\n" .
        "running the Sphinx software and readable for the user which is\n" .
        "running the webserver software.\n" .
        "\n" .
        "Note: This path does not determine where the generated Sphinx\n" .
        "configuration file will be written. You will be able to enter the\n" .
        "location for writing that file, after choosing the \"g\"enerate \n" .
        "menu option.",
        TRUE
    ),
    array (
        "USE_DELTA_UPDATES",
        0,
        "If you set this option to a true value (e.g. \"1\"), then the\n" .
        "configuration file will have support for doing \"delta updates\"\n" .
        "for the search data. This means that two different indexes will\n" .
        "be used by the Sphinx system. One index will be updated on a\n" .
        "slow schedule (like twice a day). This index will hold all data\n" .
        "for messages that were posted up to the time of indexing. The\n" .
        "other index will be updated on a really regular base (like each\n" .
        "15 minutes or even more regular). This index will hold all data\n" .
        "for messages that were posted after the last full index update.\n" .
        "\n" .
        "Note: for running the delta updates schedule, you will need to run\n".
        "MySQL server version 4.1 or higher. It will not work on older\n" .
        "versions of MySQL because of lacking features.",
        TRUE
    ),
    array (
        "MAX_SEARCH_RESULTS",
        1000,
        "This is the maximum number of search results that the Sphinx\n" .
        "search engine will return to Phorum (default is \"1000\").\n"
    ),
    array (
        "DATA_PREFIX",
        $PHORUM["DBCONFIG"]["table_prefix"],
        "This prefix is used as a prefix for all the Sphinx sources,\n" .
        "indexes and data files. You can choose anything here you like,\n" .
        "as long as this prefix is unique for the DATAPATH setting. If you\n" .
        "are storing multiple Sphinx search data sets in the same DATAPATH,\n".
        "then you would need to differentiate the DATA_PREFIXes to prevent\n" .
        "file name collisions from happening.\n" .
        "\n" .
        "If you don't know what value to use, then just use the default"
    ),
    array (
        "SEARCHD_HOST",
        "localhost",
        "This is the hostname for connecting to the\n" .
        "Sphinx searchd server (default is \"localhost\")."
    ),
    array (
        "SEARCHD_PORT",
        "3312",
        "This is is the port number for connecting to the\n" .
        "Sphinx searchd server (default is \"3312\")."
    )
);

while (TRUE)
{
    print <<<INFO
Please select the configuration option that you want to change. The most
interesting features are marked with "***". When selecting an option, you will
get some more info about the purpose of that option. You can safely return
to this screen after doing so.


INFO;

    foreach ($options as $id => $option) {
        $flag = isset($option[3]) ? "***" : "";
        print ($id+1) . ") $option[0] = $option[1] $flag\n";
    }
    print "q) Quit from this program.\n";
    print "g) Generate configuration file.\n";

    print "\n> ";
    $opt = trim(fgets(STDIN));
    if (((int)$opt > 0 && (int)$opt < count($options)))
    {
        $id = (int)$opt - 1;
        $option = $options[$id];

        print "\nOption: $option[0]\n";
        print str_repeat("-", 70) . "\n";
        print "$option[2]\n";
        print str_repeat("-", 70) . "\n";
        print "Enter new value or <ENTER> to accept the displayed current value.\n";
        print "\n";
        print $option[1] . "> ";
        $val = trim(fgets(STDIN));
        if ($val != '') {
            $options[$id][1] = $val;
        }
        print "\n";
    }
    elseif ($opt == 'q') {
        print "\nAborting ...\n\n";
        exit();
    }
    elseif ($opt == 'g') {
        print "\n";
        $errors = 0;
        foreach ($options as $option) {
            if ($option[1] === NULL || $option[1] === '') {
                print "Option \"$option[0]\" must be configured.\n";
                $errors = 1;
            }
        }
        if ($errors) {
            print "\n";
            sleep(1);
            continue;
        } else {
            break;
        }
    }
    else
    {
        print "\nIllegal option selected. Please try again ...\n\n";
        sleep(1);
        continue;
    }
}

foreach ($options as $option) {
    $PHORUM["DATA"][$option[0]] = $option[1];
}

$PHORUM["template"] = 'config_template';
ob_start();
include(phorum_get_template("sphinx_search::configuration"));
$config = ob_get_contents();
ob_end_clean();

$outfile = $PHORUM["cache"] . "/sphinx.conf-" . time();
require_once("./include/api/write_file.php");
phorum_api_write_file($outfile, $config);
print <<< CONFIGDONE
----------------------------------------------------------------------
A new configuration has been written to the location:
$outfile
----------------------------------------------------------------------

Look over the generated configuration file and see if you want to change
anything. After that, the first thing to do, would be to get Sphinx up and
running using this configuration file. For doing so, please refer to the
Sphinx documentation. The tasks that have to be taken care of are:

* Indexing your Phorum data, using the Sphinx "indexer" program.

* Running the Sphinx "searchd" program, which will let Phorum access the
  indexed search data.

* Setting up scheduled jobs for letting Sphinx update its indexes on a
  regular basis.

After setting up Sphinx, you will be ready to enable the Sphinx Search module.
You will have to configure that module from its settings page. If you wish,
this install script can update the module settings for you. Do you want
to run this update now?

CONFIGDONE;

while (TRUE)
{
    print "\nPlease enter \"yes\" or \"no\" > ";
    $yesno = trim(fgets(STDIN));
    if ($yesno == 'yes' || $yesno == 'no') { break; }
}

$prefix = $PHORUM["DATA"]["DATA_PREFIX"];

$mi = "{$prefix}_msg" .
      ($PHORUM["DATA"]["USE_DELTA_UPDATES"]?" {$prefix}_msg_delta":"");

$ai = "{$prefix}_author" .
      ($PHORUM["DATA"]["USE_DELTA_UPDATES"]?" {$prefix}_author_delta":"");

$settings = array (
    "sphinx_host"        => $PHORUM["DATA"]["SEARCHD_HOST"],
    "sphinx_port"        => $PHORUM["DATA"]["SEARCHD_PORT"],
    "message_index"      => $mi,
    "author_index"       => $ai,
    "max_search_results" => $PHORUM["DATA"]["MAX_SEARCH_RESULTS"],
);

if ($yesno == 'yes')
{
    phorum_db_update_settings(array("mod_sphinx_search" => $settings));

    print "\nThe settings for the Sphinx Search module have been updated.\n" .
          "You should only have to enable the module to get it working\n" .
          "with the Sphinx search engine.\n\n";
} else {
    print "\n" .
      "You will have to configure the settings for the module by\n" .
      "hand. Below, you will see the options that you will need to\n" .
      "use in the settings screen for this module, when running with\n" .
      "the generated Sphinx configuration file:\n\n" .
      "Sphinx IP address or hostname : {$settings["sphinx_host"]}\n" .
      "Sphinx port                   : {$settings["sphinx_port"]}\n" .
      "Message index(es) to use      : $mi\n" .
      "Author index(es) to use       : $ai\n" .
      "Maximum number of results     : {$settings["max_search_results"]}\n".
      "\n";
}

CONFIGDONE;

// No delta updates? Then we are done!
if (! $PHORUM["DATA"]["USE_DELTA_UPDATES"]) exit(0);

// Check if the incremental delta updates support table is available
// already. If not, then prompt for creating it now. Otherwise, we're done.
$incr_table = "{$PHORUM["DBCONFIG"]["table_prefix"]}_sphinx_counter";
$incr_recs = phorum_db_interact(
    DB_RETURN_ROWS,
    "DESC $incr_table",
    NULL,
    DB_MISSINGTABLEOK
);
if (!empty($incr_recs)) exit(0);


$sql = "CREATE TABLE $incr_table (\n" .
       "  counter_id int(11) unsigned NOT NULL default '0',\n" .
       "  `type` enum('author','message') NOT NULL default 'message',\n" .
       "  max_doc_id int(11) NOT NULL,\n" .
       "  PRIMARY KEY  (counter_id,`type`)\n" .
       ")";

print <<< CONFIGDONE
----------------------------------------------------------------------
An extra table "$incr_table" is required
----------------------------------------------------------------------

For supporting incremental updates for searching, an extra database
table is required. The definition of this table is:

$sql

Do you want to automatically create this table now?

CONFIGDONE;

while (TRUE)
{
    print "\nPlease enter \"yes\" or \"no\" > ";
    $yesno = trim(fgets(STDIN));
    if ($yesno == 'yes' || $yesno == 'no') { break; }
}

if ($yesno == 'yes') {
    phorum_db_interact(DB_RETURN_RES, $sql);
    print "\nTable \"$incr_table\" created.\n\n";
}


exit(0);
?>
