# WordPress CRUD web-api demo

A boilerplate for create custom rest endpoint.

## Contents

The WordPress Plugin `webapi.php` includes the following:

* `crud-controller.php`. A custom API Endpoint (`vendor/v1/route`).
* `crud-dao.php`. The database access layer.
* `crud.php`. A single-page test application.

## Features

* CRUD operations with WP standard pattern:
  * `GET /wp-json/vendor/v1/route` to get a collection of items.
  * `GET /wp-json/vendor/v1/route/123` to get a single item with ID 123.
  * `POST /wp-json/vendor/v1/route` to create a new item.
  * `DELETE /wp-json/vendor/v1/route/123` to delete item with ID 123.
  * `GET /wp-json/vendor/v1/` to get the endpoint schema.

* Wordpress cookies based authentication.
  **note**: The nonce is passed to the page by the server as part of the page, so we must use ssl in production environments.

* Database conflicts detection.

## Installation

* Download the project into your plugins folder.
* Activate the plugin through the 'Plugins' menu in WordPress


## Usage

* Navigate to `YOUR_WP_URL/crud_test/` to open the test page
