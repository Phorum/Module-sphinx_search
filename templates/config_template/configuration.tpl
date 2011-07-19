{! Important: lines that end with a template var need an additional ending }
{! space, otherwise the template engine will stick the next line to the }
{! end of it. }
#############################################################################
## data source definition
#############################################################################

# This source defines the base information that is used for
# configuring the other sources.
source {DATA_PREFIX}_base
{
    type                = mysql
    strip_html          = 0
    index_html_attrs    =

    sql_host            = {DBHOST} 
    sql_user            = {DBUSER} 
    sql_pass            = {DBPASS} 
    sql_db              = {DBNAME} 
    sql_port            = {DBPORT} 

    sql_group_column    = forum_id
    sql_group_column    = thread
    sql_date_column     = datestamp
}

# This source defines how to retrieve messages from the database.
source {DATA_PREFIX}_msg: {DATA_PREFIX}_base
{
{IF USE_DELTA_UPDATES}
    # Write the maximum message_id in the sphinx counter table, so
    # we can use that as a starting point for delta updates.
    sql_query_pre = \
        REPLACE INTO {DBPREFIX}_sphinx_counter \
          SELECT 1, 'message', MAX(message_id) \
          FROM   {MESSAGE_TABLE} \
          WHERE  status=2

    # Select all messages up to and including the recorded maximum message_id.
    sql_query = \
        SELECT message_id, \
               thread, \
               forum_id, \
               datestamp, \
               author, \
               subject, \
               body \
        FROM   {MESSAGE_TABLE} \
        WHERE  message_id <= ( \
                 SELECT max_doc_id  \
                 FROM   {DBPREFIX}_sphinx_counter \
                 WHERE  counter_id = 1 AND type = 'message' ) AND \
               status=2
{ELSE}
    # Select all messages that are in the database.
    sql_query = \
        SELECT message_id, thread, forum_id, datestamp, author, subject, body \
        FROM   {MESSAGE_TABLE}
{/IF}
}

{IF USE_DELTA_UPDATES}
# This source defines how to retrieve messsages from the database that
#  are posted after the last full indexing run.
source {DATA_PREFIX}_msg_delta: {DATA_PREFIX}_base
{
    # Retrieve all messages that have a higher message_id than the maximum
    # message_id that was recorded during a full indexing run.
    sql_query = \
        SELECT message_id, \
               thread, \
               forum_id, \
               datestamp, \
               author, \
               subject, \
               body \
        FROM   {MESSAGE_TABLE} \
        WHERE  message_id > ( \
                 SELECT max_doc_id  \
                 FROM   {DBPREFIX}_sphinx_counter \
                 WHERE  counter_id = 1 AND \
                        type = 'message' ) AND \
               status=2
}
{/IF}

#############################################################################
## index definition
#############################################################################

# This defines the message index.
index {DATA_PREFIX}_msg
{
    morphology          = none
    stopwords           =
    min_word_len        = 1
    charset_type        = sbcs

    source              = {DATA_PREFIX}_msg
    path                = {DATAPATH}/{DATA_PREFIX}_msg
}


{IF USE_DELTA_UPDATES}
# This defines the delta message index.
index {DATA_PREFIX}_msg_delta: {DATA_PREFIX}_msg
{
    source              = {DATA_PREFIX}_msg_delta
    path                = {DATAPATH}/{DATA_PREFIX}_msg_delta
}
{/IF}

#############################################################################
## indexer settings
#############################################################################

indexer
{
    mem_limit           = 32M
}

#############################################################################
## searchd settings
#############################################################################

searchd
{
    address             = {SEARCHD_HOST} 
    port                = {SEARCHD_PORT} 
    log                 = {DATAPATH}/{DATA_PREFIX}_searchd.log
    query_log           = {DATAPATH}/{DATA_PREFIX}_query.log

    read_timeout        = 5
    max_children        = 30
    pid_file            = {DATAPATH}/{DATA_PREFIX}_searchd.pid
    max_matches         = {MAX_SEARCH_RESULTS} 
}

