# Pagination and Search

There is significant load time if you want to populate the data table with many rows (greater than 1000) from the Data REST API.

To paginate results in your API response, you add the following parameters to the query:

#### ``per_page``

This is the page size, default is ``10``

#### ``page``

Determines the current page, e.g if value is ``5`` and per page is ``10`` from a list of ``100`` records, it means records returned are from ``50 - 60``

#### ``order_by``

Determines key to order by. Default items is ``created_at`` column in model tables

#### ``order_method``

This is the method to order data, accepted values are ``asc`` and ``desc``
