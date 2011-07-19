{IF SEARCH->match_type "USER_ID"}
  <h1>{HEADING}</h1>
  <p>{DESCRIPTION}</p>
{/IF}

{! ------- A notification in case no results were found ------- }

{IF SEARCH->noresults}
  <div align="center">
    <div class="phorum-titleblock" style="text-align: left;">
      {LANG->NoResults}
    </div>
    <div class="phorum-block" style="text-align: left;">
      <div class="PhorumFloatingText">
        {LANG->NoResultsHelp}
      </div>
    </div>
    <div class="phorum-endblock"></div>
  </div>
  <br/>
{/IF}

{! ------- Show search results ------- }

{IF SEARCH->showresults}

  {INCLUDE "render.paging"}

  <div class="phorum-block phorum-altblock" style="text-align: left">
    {LANG->Results} {RANGE_START} - {RANGE_END} {LANG->of} {TOTAL}
  </div>

  {LOOP MATCHES}
    <div class="phorum-block">

      <div style="float:right; width:120px; white-space: nowrap">{MATCHES->datestamp}</div>
      <div style="margin-right: 240px">{MATCHES->number}.&nbsp;<a class="phorum-speciallink" href="{MATCHES->URL->READ}">{MATCHES->subject}</a></div>

        {MATCHES->short_body}<br/>
        <b>{LANG->Postedby}: </b> {MATCHES->author}<br/>
        <b>{LANG->Forum}: <a href="{MATCHES->URL->LIST}">{MATCHES->forum_name}</a></b>
    </div>
  {/LOOP MATCHES}
 
  {INCLUDE "render.paging"}
 
  <div class="phorum-endblock"></div>

  <br/>
  {INCLUDE "render.toplink"}
  <br/><br/>

{/IF}

{! ------- Show search form ------- }

{IF NOT SEARCH->match_type "USER_ID"}

  <div class="phorum-titleblock">{LANG->Search}</div>
    <div class="phorum-block" id="search-form">

        <form action="{URL->ACTION}" method="get">
            <input type="hidden" name="phorum_page" value="search" />
            <input type="hidden" name="forum_id" value="{SEARCH->forum_id}" />
            {POST_VARS}
            {LANG->SearchMessages}:<br />
            <input type="text" name="search" id="phorum_search_message" size="30" maxlength="" value="{SEARCH->safe_search}" />
            <select name="match_type">
                <option value="ALL" {IF SEARCH->match_type "ALL"}selected="selected"{/IF}>{LANG->MatchAll}</option>
                <option value="ANY" {IF SEARCH->match_type "ANY"}selected="selected"{/IF}>{LANG->MatchAny}</option>
                <option value="PHRASE" {IF SEARCH->match_type "PHRASE"}selected="selected"{/IF}>{LANG->MatchPhrase}</option>
            </select>

            {! Added for the sphinx search module }
            <select name="sphinx_match_fields">
                <option value="BOTH" {IF SEARCH->sphinx_match_fields "BOTH"}selected="selected"{/IF}>{LANG->mod_sphinx_search->MatchBoth}</option>
                <option value="BODY" {IF SEARCH->sphinx_match_fields "BODY"}selected="selected"{/IF}>{LANG->mod_sphinx_search->MatchBody}</option>
                <option value="SUBJECT" {IF SEARCH->sphinx_match_fields "SUBJECT"}selected="selected"{/IF}>{LANG->mod_sphinx_search->MatchSubject}</option>
            </select>

            <input type="submit" value="{LANG->Search}" /><br />
            <br />
            {LANG->SearchAuthors}:<br />
            <input type="text" id="phorum_search_author" name="author" size="30" maxlength="" value="{SEARCH->safe_author}" /><br />
            <br />
            {LANG->Forums}:<br />
            <select name="match_forum[]" size="{SEARCH->forum_list_length}" multiple="multiple">
                <option value="ALL" {IF SEARCH->match_forum "ALL"}selected="selected"{/IF}>{LANG->MatchAllForums}</option>
                {LOOP SEARCH->forum_list}
                    {IF SEARCH->forum_list->folder_flag}
                        <optgroup style="padding-left: {SEARCH->forum_list->indent}px" label="{SEARCH->forum_list->name}" />
                    {ELSE}
                        <option style="padding-left: {SEARCH->forum_list->indent}px" value="{SEARCH->forum_list->forum_id}" {IF SEARCH->forum_list->selected}selected="selected"{/IF}>{SEARCH->forum_list->name}</option>
                    {/IF}
                {/LOOP SEARCH->forum_list}
            </select>
            <br />
            <br />
            {LANG->Options}:<br />
            {! Changed for the sphinx search module }
            <select name="sphinx_match_threads">
                <option value="0" {IF SEARCH->sphinx_match_threads "0"}selected="selected"{/IF}>{LANG->mod_sphinx_search->MatchMessages}</option>
                <option value="1" {IF SEARCH->sphinx_match_threads "1"}selected="selected"{/IF}>{LANG->mod_sphinx_search->MatchThreads}</option>
                <option value="2" {IF SEARCH->sphinx_match_threads "2"}selected="selected"{/IF}>{LANG->mod_sphinx_search->MatchThreadsBest}</option>
            </select>
            &nbsp;
            &nbsp;
            <select name="match_dates">
                <option value="30" {IF SEARCH->match_dates 30}selected="selected"{/IF}>{LANG->Last30Days}</option>
                <option value="90" {IF SEARCH->match_dates 90}selected="selected"{/IF}>{LANG->Last90Days}</option>
                <option value="365" {IF SEARCH->match_dates 365}selected="selected"{/IF}>{LANG->Last365Days}</option>
                <option value="0" {IF SEARCH->match_dates 0}selected="selected"{/IF}>{LANG->AllDates}</option>
            </select>
            <br />
        </form>
    </div>
  </div>
  <div class="phorum-endblock"></div>

{/IF}




