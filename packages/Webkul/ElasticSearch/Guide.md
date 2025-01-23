# Elasticsearch Integration Guide

## Steps to Index a New Table in Elasticsearch

### Step 1: Add the Indexer Command

1. Create the Indexer Command File:

    Create a new indexer command for your table:
    _ex:_
~~~
    /packages/Webkul/ElasticSearch/src/Console/Command/NewTableIndexer.php
~~~

2. Add logics for the new table:

    In your _NewTableIndexer.php_ file, define the index name for the new table _(e.g., newtable)_, and implement the following logic:
        **Index existing records** from the database into Elasticsearch.
        **Delete stale records** from Elasticsearch for that table.
        **Delete all records** from Elasticsearch if no records are found in the database for the table.

    **Note:** _Follow the same pattern as the other indexed tables (e.g., ProductIndexer.php) to maintain consistency._

### Step 2: Add an Observer for the Table

1. Create the Observer File:

    Create an observer for your new table:
    _ex:_
~~~
    /packages/Webkul/ElasticSearch/src/Observers/NewTable.php
~~~

2. Implement Event Handlers:

    In your observer class, implement the following methods to handle the respective events:
        **created:** Index the new record into Elasticsearch.
        **updated:** Update the corresponding document in Elasticsearch.
        **deleted:** Remove the document from Elasticsearch.

    **Tip:** _Refer to the existing observers for other models (e.g., ProductObserver.php) to ensure consistent code structure._

### Step 3: Register the Observer

1. Register the Observer in Service Provider:

    Open the _ElasticSearchServiceProvider.php_ file:
~~~
        /packages/Webkul/ElasticSearch/src/Providers/ElasticSearchServiceProvider.php
~~~

    In the boot method, register the observer for your new table:
    _ex:_
~~~
        NewTableModel::observe(NewTableObserver::class);
~~~

### Step 4: Register the Indexer Command:

1. Register the Indexer in Service Provider:

    Open the _ElasticSearchServiceProvider.php_ file:
~~~
        /packages/Webkul/ElasticSearch/src/Providers/ElasticSearchServiceProvider.php
~~~

    In the registerCommands method, register your _NewTableIndexer_ command after the CategoryIndexer::class:
    _ex:_
~~~
        NewTableIndexer::class,
~~~

### Step 5: Add logic to clear index of new table

1. Modify Reindexer Command:

    Open the _Reindexer.php_ command file:
~~~
    /packages/Webkul/ElasticSearch/src/Console/Command/Reindexer.php
~~~

2. Add Logic for New Table:

    Add the logic to clear or re-index the new table in Elasticsearch. Follow the same pattern used for other tables like _ProductIndex_ and _CategoryIndex_.

### Step 5: Run and test the Commands

1. Run the Indexer Command:

    From the command line, run the command which you created to index your new table:
    _ex:_
~~~
        php artisan newtable:index
~~~

2. Re-index the Table (Optional):

    If you need to re-index the new table, use the following command to clear the index:
~~~
        php artisan elastic:clear
~~~
