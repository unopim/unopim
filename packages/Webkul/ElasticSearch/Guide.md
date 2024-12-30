# Elasticsearch Integration Guide

## Steps to Index a New Table in Elasticsearch

### Step 1: Update the Indexer Command

1. Open the file:
~~~
/packages/Webkul/ElasticSearch/src/Console/Command/Indexer.php
~~~

2. Add support for the new table:

    Define the index name for the table (e.g., my_table).

    Implement logic to:
        Index existing records from the database into Elasticsearch.
        Delete stale records from Elasticsearch for this table.

    Follow the same pattern as the other tables already indexed.

### Step 2: Add an Observer for the Table

1. Create a new Observer for the table:
~~~
/packages/Webkul/ElasticSearch/src/Observers/YourTableObserver.php
~~~

2. Implement methods to handle the following events:

    created: Index the new record into Elasticsearch.

    updated: Update the corresponding document in Elasticsearch.

    deleted: Remove the document from Elasticsearch.

3. Use the existing Observers as a reference to ensure consistency.

### Step 3: Register the Observer

1. Open the file:
~~~
/packages/Webkul/ElasticSearch/src/Providers/ElasticSearchServiceProvider.php
~~~

2. Locate the boot method and register the Observer:
~~~
    YourTableModel::observe(YourTableObserver::class);
~~~
