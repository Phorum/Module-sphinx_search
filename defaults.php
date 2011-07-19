<?php

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"])) {
    $GLOBALS["PHORUM"]["mod_sphinx_search"] = array();
}

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]["sphinx_host"])) {
    $GLOBALS["PHORUM"]["mod_sphinx_search"]["sphinx_host"] = "localhost";
}

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]["sphinx_port"])) {
    $GLOBALS["PHORUM"]["mod_sphinx_search"]["sphinx_port"] = 3312;
}

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]["message_index"])) {
    $GLOBALS["PHORUM"]["mod_sphinx_search"]["message_index"] =
        "phorum5_msg_d phorum5_msg";
}

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]["author_index"])) {
    $GLOBALS["PHORUM"]["mod_sphinx_search"]["author_index"] =
        "phorum5_author_d phorum5_author";
}

if (! isset($GLOBALS["PHORUM"]["mod_sphinx_search"]["max_search_results"])) {
    $GLOBALS["PHORUM"]["mod_sphinx_search"]["max_search_results"] = 1000;
}

?>
