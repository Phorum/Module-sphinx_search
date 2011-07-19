<?php

require_once("./mods/sphinx_search/defaults.php");

function mod_sphinx_change_defaults($args)
{
    if(isset($args["page"])) {
        return $args;
    }

    $args['match_dates'] = "0";

    return $args;
}

function mod_sphinx_search_redirect($url_parameters)
{
    // Add the extra form fields to the primary search redirect URL.
    // Apply some defaults if the settings are not available.

    if (isset($_GET["sphinx_match_fields"])) {
        $url_parameters[] = 'sphinx_match_fields='.urlencode($_GET["sphinx_match_fields"]);
    } else {
        $url_parameters[] = 'sphinx_match_fields=BOTH';
    }

    if (isset($_GET["sphinx_match_threads"])) {
        $url_parameters[] = 'sphinx_match_threads='.(int)$_GET["sphinx_match_threads"];
    } else {
        $url_parameters[] = 'sphinx_match_threads=0';
    }

    return $url_parameters;
}

function mod_sphinx_do_search($data_array)
{
    $PHORUM=$GLOBALS['PHORUM'];

    $settings = $PHORUM["mod_sphinx_search"];
    settype($settings["sphinx_port"], "int");

    // Initialize the Sphinx client.
    include './mods/sphinx_search/sphinxapi.php';
    $sphinx = new SphinxClient();
    $sphinx->SetServer($settings["sphinx_host"], $settings["sphinx_port"]);
    $sphinx->SetMatchMode(SPH_MATCH_EXTENDED);

    // Set the limits for paging.
    $sphinx->SetLimits($data_array['offset'], $data_array['length']);

    // In this array, we'll build the Sphinx queries to run.
    $match_strs = array();

    // Handle search on body or subject terms.
    $search = trim($data_array['search']);

    if($search != '')
    {
        $tokens = array();

        // Prepare searching for an exact phrase.
        if ($data_array['match_type'] == "PHRASE")
        {
            $tokens[] = '"'. str_replace('"', '', $search) .'"';
        }
        // Prepare searching for separate query terms.
        else
        {
            // Surround the query with spaces so matching is easier.
            $search = " $search ";

            // Pull all grouped terms from the query,
            // e.g. (nano mini)
            $paren_terms = array();
            if ( strstr($search, '(') ) {
                preg_match_all('/ ([+\-~]*\(.+?\)) /', $search, $m);
                $search = preg_replace('/ [+\-~]*\(.+?\) /', ' ', $search);
                $paren_terms = $m[1];
            }

            // Pull all double quoted terms from the query,
            // e.g. "iMac DV" or -"iMac DV"
            $quoted_terms = array();
            if ( strstr($search, '"') ) {
                preg_match_all('/ ([+\-~]*".+?") /', $search, $m);
                $search = preg_replace('/ [+\-~]*".+?" /', ' ', $search);
                $quoted_terms = $m[1];
            }

            // Finally, pull the rest words from the query.
            $norm_terms = preg_split("/\s+/", $search, 0, PREG_SPLIT_NO_EMPTY);

            // Replace + and ~ in the input search; no use for them in Sphinx.
            $norm_terms = preg_replace( '/^[+~]/', '', $norm_terms );

            // Merge all parts together.
            $tokens =  array_merge( $quoted_terms, $paren_terms, $norm_terms );
        }

        // What fields to search on. This can be used to restrict the
        // search to body or subject only.
        $search_fields = array('@body', '@subject');
        if (isset($PHORUM["args"]["sphinx_match_fields"])) {
            if ($PHORUM["args"]["sphinx_match_fields"] == 'BODY') {
                $search_fields = array('@body');
            } elseif ($PHORUM["args"]["sphinx_match_fields"] == 'SUBJECT') {
                $search_fields = array('@subject');
            }

            $GLOBALS["PHORUM"]["DATA"]["SEARCH"]["sphinx_match_fields"] =
                htmlspecialchars($PHORUM["args"]["sphinx_match_fields"]);
        }

        // putting together the actual search
        if ($data_array['match_type'] == 'ALL' ||
            $data_array['match_type'] == 'ANY')
        {
            $subtokens = array();
            $negates   = '';
            foreach ($tokens as $token) {
                if (substr($token, 0, 1) == '-') {
                    foreach ($search_fields as $field) {
                        $negates .= "$field $token ";
                    }
                } else {
                    $parts = array();
                    foreach ($search_fields as $field) {
                        $parts[] = "$field $token";
                    }
                    $subtokens[] = implode(' | ', $parts);
                }
            }

            $glue = $data_array['match_type'] == 'ALL' ? ' & ' : ' | ';

            $match_str = $negates . implode($glue, $subtokens);
        }
        elseif ($data_array['match_type'] == 'PHRASE')
        {
            $subtokens = array();
            foreach ($search_fields as $field) {
                $subtokens[] = "$field " . $tokens[0];
            }
            $match_str = implode(' | ', $subtokens);
        }
        else
        {
            // Return search control to Phorum in case the search type
            // isn't handled by the module.
            return $data_array;
        }

        $match_strs[] = $match_str;
    }

    // add author matching
    $author = trim($data_array['author']);
    if ($author != '') {
        // The USER_ID match_type is not handled by the Sphinx backend.
        if ($data_array['match_type'] == 'USER_ID') {
            return $data_array;
        } else {
            $match_strs[] ="@author ".$author;
        }
    }

    $match_str = implode(" & ",$match_strs);

    // set the timeframe to search
    if($data_array['match_dates'] > 0)
    {
        $min_ts = time() - 86400 * $data_array['match_dates'];
        $max_ts = time();
        $sphinx->SetFilterRange("datestamp", $min_ts, $max_ts);
    }

    // add the forum(s) to search
    if ($data_array['match_forum'] == 'THISONE')
    {
        $forumid_clean = (int)$PHORUM['forum_id'];
        $sphinx->SetFilter ( "forum_id", array($forumid_clean) );
    }
    elseif(!empty($data_array['match_forum']))
    {
        // We have to check what forums they can read first.
        $allowed_forums = phorum_api_user_check_access(
            PHORUM_USER_ALLOW_READ,
            PHORUM_ACCESS_LIST
        );

        // Prepare forum_id restriction.
        $match_forum_arr = explode(",", $data_array['match_forum']);
        foreach ($match_forum_arr as $forum_id)
        {
            if ($forum_id=="ALL") {
                $search_forums = $allowed_forums;
                break;
            }
            if (isset($allowed_forums[$forum_id])){
                $search_forums[] = (int)$forum_id;
            }
        }

        // If they are not allowed to search any forums or if the currently
        // active forum is not readable, then return empty handed.
        if(empty($allowed_forums) || ($PHORUM['forum_id']>0 &&
           !in_array($PHORUM['forum_id'], $allowed_forums)))
        {
            $data_array['results']  = array();
            $data_array['totals']   = 0;
            $data_array['continue'] = 0;
            $data_array['raw_body'] = 1;

            return $data_array;
        }

        $sphinx->SetFilter ( "forum_id", $search_forums );
    }

    // Set the sort-mode.
    $sphinx->SetSortMode(SPH_SORT_ATTR_DESC, 'datestamp');

    // Handle thread-grouping.
    if (!empty($PHORUM['args']['sphinx_match_threads']) ||
        $data_array['match_threads']) {
        $sphinx->SetGroupBy('thread', SPH_GROUPBY_ATTR);
    }
    if (!empty($PHORUM['args']['sphinx_match_threads'])) {
        $GLOBALS["PHORUM"]["DATA"]["SEARCH"]["sphinx_match_threads"] =
            htmlspecialchars($PHORUM["args"]["sphinx_match_threads"]);
    }

    // Run the actual query through Sphinx.
    $results = $sphinx->Query($match_str, $settings['message_index']);

    // Show a warning on screen if there was a Sphinx problem.
    $error = $sphinx->GetLastError();
    if (!empty($error)) {
        $GLOBALS['PHORUM']['DATA']['GLOBAL_ERROR'] =
            'Sphinx search engine error: ' .
            htmlspecialchars($error);
    }

    // if no messages were found, then return empty handed.
    if (empty($results["matches"]))
    {
        $data_array['results']  = array();
        $data_array['totals']   = 0;
        $data_array['continue'] = 0;
        $data_array['raw_body'] = 1;

        return $data_array;
    }

    // Retrieve the messages that we found from the database.
    if ((!empty($PHORUM['args']['sphinx_match_threads']) &&
        $PHORUM['args']['sphinx_match_threads'] == 1) ||
        (empty($PHORUM['args']['sphinx_match_threads']) &&
         !empty($data_array['match_threads'])))
    {
        $message_ids = array();
        foreach ($results['matches'] as $id => $data) {
            $message_ids[] = $data['attrs']['thread'];
        }
    } else {
        $message_ids = array_keys($results['matches']);
    }
    $found_messages = phorum_db_get_message($message_ids, 'message_id', TRUE);

    // Sort them in reverse order of the message_id, to automagically
    // sort them by date desc this way.
    krsort($found_messages);
    reset($found_messages);

    // Prepare the array for letting Sphinx build highlighted excerpts.
    require_once('./include/format_functions.php');
    $found_messages = phorum_format_messages($found_messages);
    $docs=array();
    foreach($found_messages as $id => $data)
    {
        $found_messages[$id]['subject'] = strip_tags($data['subject']);
        $docs[] = phorum_strip_body($data['body']);
    }

    $words = implode(" ",array_keys($results['words']));

    $opts = array('chunk_separator'=>' [...] ');

    // The excerpts index is mainly used for charsets and mapping tables.
    // It always only needs one index. We extract one index from the
    // message index.
    $excerpts_indexs = explode(" ", $settings["message_index"]);
    $excerpts_index  = $excerpts_indexs[0];

    // build highlighted excerpts
    $highlighted = $sphinx->BuildExcerpts($docs,$excerpts_index,$words,$opts);

    // Show a warning on screen if there was a Sphinx problem.
    $error = $sphinx->GetLastError();
    if (!empty($error)) {
        $GLOBALS['PHORUM']['DATA']['GLOBAL_ERROR'] =
            'Sphinx search engine error: ' .
            htmlspecialchars($error);
    }

    $cnt=0;
    foreach($found_messages as $id => $content) {
    // foreach($found_messages as $id => $content) {
        $found_messages[$id]['short_body'] = $highlighted[$cnt];
        $cnt++;
    }

    $data_array['results']=$found_messages;
    // we need the total results
    $data_array['totals']=$results['total_found'];

    if($data_array['totals'] > $settings["max_search_results"]) {
        $data_array['totals'] = $settings["max_search_results"];
    }

    // don't run the default search
    $data_array['continue']=0;
    // tell it to leave the body alone
    $data_array['raw_body']=1;

    return $data_array;
}

function mod_sphinx_search_output($template)
{
    // Apply some defaults for the Sphinx module specific fields.
    if (empty($GLOBALS["PHORUM"]["DATA"]["SEARCH"]["sphinx_match_fields"])) {
        $GLOBALS["PHORUM"]["DATA"]["SEARCH"]["sphinx_match_fields"] = 'BOTH';
    }
    if (empty($GLOBALS["PHORUM"]["DATA"]["SEARCH"]["sphinx_match_threads"])) {
        $GLOBALS["PHORUM"]["DATA"]["SEARCH"]["sphinx_match_threads"] = 0;
    }

    // Use our own search template, so we can add a feature for
    // searching only in the header or body.
    return 'sphinx_search::search';
}

?>
