<?php

/**
* REST API: WP_REST_Comments_Controller class
*
* @package WordPress
* @subpackage REST_API
* @since 4.7.0
*/

require(dirname(__FILE__) . '/crud-dao.php');

class Crud_Controller extends WP_REST_Controller
{

  /**
  * Instance of the database access layer.
  *
  * @since 4.7.0
  * @access protected
  * @var Crud_dao
  */
  protected $dao;

  public function __construct()
  {
    $this->dao = new Crud_dao();

    $version = '1';
    $this->namespace = 'vendor/v' . $version;
    $this->rest_base = 'route';
  }

  /**
  * Register the routes for the objects of the controller.
  */
  public function register_routes()
  {
    register_rest_route($this->namespace, '/' . $this->rest_base, array(
      array(
        'methods'         => WP_REST_Server::READABLE,
        'callback'        => array( $this, 'query_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' ),
        'args'                => $this->get_collection_params(),
      ),
      array(
        'methods'         => WP_REST_Server::CREATABLE,
        'callback'        => array( $this, 'create_item' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
        'args'            => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
      ),
      'schema' => array( $this, 'get_public_item_schema' ),
    ));
    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
      'args' => array(
        'id' => array(
          'description' => __('Unique identifier for the item.'),
          'type'        => 'integer',
          'sanitize_callback'  => 'absint',
        ),
      ),
      array(
        'methods'         => WP_REST_Server::READABLE,
        'callback'        => array( $this, 'query_items' ),
        'permission_callback' => array( $this, 'get_item_permissions_check' ),
        'args'                => $this->get_collection_params(),
      ),
      array(
        'methods'         => WP_REST_Server::EDITABLE,
        'callback'        => array( $this, 'update_item' ),
        'permission_callback' => array( $this, 'update_item_permissions_check' ),
        'args'            => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
      ),
      array(
        'methods'  => WP_REST_Server::DELETABLE,
        'callback' => array( $this, 'delete_item' ),
        'permission_callback' => array( $this, 'delete_item_permissions_check' ),
        'args'     => array(
          'force'    => array(
            'default'      => false,
          ),
        ),
      ),
      'schema' => array( $this, 'get_public_item_schema' ),
    ));
    // register_rest_route( $this->namespace, '/' . $this->rest_base . '/drop', array(
    //   'methods'         => WP_REST_Server::READABLE,
    //   'callback' => array( $this, 'drop_table' ),
    //   'permission_callback' => array( $this, 'delete_item_permissions_check' ),
    //   'args'     => array(
    //     'force'    => array(
    //       'default'      => false,
    //     ),
    //   ),
    // ) );
    // register_rest_route( $this->namespace, '/' . $this->rest_base . '/create', array(
    //   'methods'         => WP_REST_Server::READABLE,
    //   'callback' => array( $this, 'create_table' ),
    //   'permission_callback' => array( $this, 'delete_item_permissions_check' ),
    //   'args'     => array(
    //     'force'    => array(
    //       'default'      => false,
    //     ),
    //   ),
    // ) );
  }

  /**
  * Get one or more items from the collection
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|WP_REST_Response
  */
  public function query_items($request)
  {
    //get parameters from request
    $params = $request->get_params();
    if (is_wp_error($params)) {
      return $params;
    }

    $items = $this->dao->query_item($request);

    if (is_wp_error($items)) {
      if ('db_error' === $items->get_error_code()) {
        $items->add_data(array( 'status' => 500 ));// Internal server error
      } else {
        $items->add_data(array( 'status' => 400 ));// Bad request
      }

      return $items;
    }

    $data = array();
    foreach ($items as $item) {
      $itemdata = $this->prepare_item_for_response($item, $request);
      $data[] = $this->prepare_response_for_collection($itemdata);
    }

    return new WP_REST_Response($data, 200);
  }

  /**
  * Create one item from the collection
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|WP_REST_Request
  */
  public function create_item($request)
  {
    $prepared_item = $this->prepare_item_for_database($request);

    if (is_wp_error($prepared_item)) {
      return $prepared_item;
    }

    $item_id = $this->dao->insert_item($prepared_item);

    if (is_wp_error($item_id)) {
      if ('db_error' === $item_id->get_error_code()) {
        $item_id->add_data(array( 'status' => 500 ));// Internal server error
      } else {
        $item_id->add_data(array( 'status' => 400 ));// Bad request
      }

      return $item_id;
    }

    $item = $this->dao->query_item($item_id);

    if (is_array($item)) {
      return new WP_REST_Response($item, 200);
    }

    return new WP_Error('cant-create', __('message', 'text-domain'), array( 'status' => 500 ));// Internal server error
  }

  /**
  * Update one item from the collection
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|WP_REST_Request
  */
  public function update_item($request)
  {
    $prepared_item = $this->prepare_item_for_database($request);

    if (function_exists('slug_some_function_to_update_item')) {
      $data = slug_some_function_to_update_item($prepared_item);
      if (is_array($data)) {
        return new WP_REST_Response($data, 200);
      }
    }

    return new WP_Error('cant-update', __('message', 'text-domain'), array( 'status' => 500 ));// Internal server error
  }

  /**
  * Delete one item from the collection
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|WP_REST_Request
  */
  public function delete_item($request)
  {

    $prepared_item = new stdClass;

    $schema = $this->get_item_schema();

    // required arguments.
    if (isset($request['id'])) {
      $prepared_item->id = absint($request['id']);
    }

    // Filters user data before insertion via the REST API.
    apply_filters('rest_pre_delete_item', $prepared_item, $request);

    if (is_wp_error($prepared_item)) {
      return $prepared_item;
    }

    $item_id = $this->dao->delete_item($prepared_item);

    if (is_wp_error($item_id)) {
      if ('db_error' === $item_id->get_error_code()) {
        $item_id->add_data(array( 'status' => 500 ));// Internal server error
      } else {
        $item_id->add_data(array( 'status' => 400 ));// Bad request
      }

      return $item_id;
    } else {
      return new WP_REST_Response($item_id, 200);
    }

    return new WP_Error('cant-delete', __('message', 'text-domain'), array( 'status' => 500 ));// Internal server error
  }

  // /**
  // * Delete one item from the collection
  // *
  // * @param WP_REST_Request $request Full data about the request.
  // * @return WP_Error|WP_REST_Request
  // */
  // public function drop_table($request)
  // {
  //   $deleted = $this->dao->drop_table();
  //   if (! is_wp_error($deleted)) {
  //     return new WP_REST_Response(true, 200);
  //   }
  //
  //   return new WP_Error('cant-delete', __('message', 'text-domain'), array( 'status' => 500 ));// Internal server error
  // }
  // /**
  // * Delete one item from the collection
  // *
  // * @param WP_REST_Request $request Full data about the request.
  // * @return WP_Error|WP_REST_Request
  // */
  // public function create_table($request)
  // {
  //   $deleted = $this->dao->create_table();
  //   if (! is_wp_error($deleted)) {
  //     return new WP_REST_Response(true, 200);
  //   }
  //
  //   return new WP_Error('cant-create', __('message', 'text-domain'), array( 'status' => 500 ));
  // }

  /**
  * Check if a given request has access to get items
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|bool
  */
  public function get_items_permissions_check($request)
  {
    // return true; //<--use to make readable by all
    return current_user_can('read');
  }

  /**
  * Check if a given request has access to get a specific item
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|bool
  */
  public function get_item_permissions_check($request)
  {
    // return true; //<--use to make readable by all
    return $this->get_items_permissions_check($request);
  }

  /**
  * Check if a given request has access to create items
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|bool
  */
  public function create_item_permissions_check($request)
  {
    // return true; //<--use to make readable by all
    return current_user_can('edit_pages');
  }

  /**
  * Check if a given request has access to update a specific item
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|bool
  */
  public function update_item_permissions_check($request)
  {
    // return true; //<--use to make readable by all
    return $this->create_item_permissions_check($request);
  }

  /**
  * Check if a given request has access to delete a specific item
  *
  * @param WP_REST_Request $request Full data about the request.
  * @return WP_Error|bool
  */
  public function delete_item_permissions_check($request)
  {
    // return true; //<--use to make readable by all
    return current_user_can('delete_pages');
  }

  /**
  * Prepare the item for create or update operation
  *
  * @param WP_REST_Request $request Request object
  * @return WP_Error|object $prepared_item
  */
  protected function prepare_item_for_database($request)
  {
    $prepared_item = new stdClass;

    $schema = $this->get_item_schema();

    if (isset($request['name']) && ! empty($schema['properties']['name'])) {
      $prepared_item->name = $request['name'];
    }

    if (isset($request['updated']) && ! empty($schema['properties']['updated'])) {
      $prepared_item->updated = $request['updated'];
    }

    if (isset($request['id'])) {
      $prepared_item->id = absint($request['id']);
    }

    if (isset($request['data']) && ! empty($schema['properties']['data'])) {
      $prepared_item->data = $request['data'];
    }

    return apply_filters('rest_pre_insert_item', $prepared_item, $request);
  }

  /**
  * Prepare the item for the REST response
  *
  * @param mixed $item WordPress representation of the item.
  * @param WP_REST_Request $request Request object.
  * @return mixed
  */
  public function prepare_item_for_response($item, $request)
  {
    $data = $this->add_additional_fields_to_object($item, $request);

    // Wrap the data in a response object.
    $response = rest_ensure_response($data);

    return apply_filters('rest_prepare_item', $response, $item, $request);
  }

  /**
  * Retrieves the item schema, conforming to JSON Schema.
  *
  * @since 4.7.0
  * @access public
  *
  * @return array Item schema data.
  */
  public function get_item_schema()
  {
    $schema = array(
      '$schema'    => 'http://json-schema.org/schema#',
      'title'      => 'crud',
      'type'       => 'object',
      'properties' => array(
        'id'          => array(
          'description' => __('Unique identifier for the item.'),
          'type'        => 'integer',
          'context'     => array( 'embed', 'view', 'edit' ),
          'readonly'    => true,
          'sanitize_callback'  => 'absint',
        ),
        'name'        => array(
          'description' => __('Display name for the item.'),
          'type'        => 'string',
          'context'     => array( 'embed', 'view', 'edit' ),
          'required'    => false,
        ),
        'updated'   => array(
          'description' => __('Item update timestamp'),
          'type'        => 'string',
          'context'     => array( 'embed', 'view', 'edit' ),
          'readonly'    => true,
        ),
        'created'   => array(
          'description' => __('Item creation timestamp'),
          'type'        => 'string',
          'context'     => array( 'embed', 'view', 'edit' ),
          'readonly'    => true,
        ),
        'data'        => array(
          'description' => __('Opaque JSON string'),
          'type'        => 'string',
          'context'     => array( 'edit' ),
          'required'    => false,
        ),
      ),
    );

    return $this->add_additional_fields_schema($schema);
  }

  /**
  * Retrieves the query params for collections.
  * @return array Collection parameters.
  */
  public function get_collection_params()
  {
    // $query_params = parent::get_collection_params();
    // $query_params['context']['default'] = 'view';

    $query_params['fields'] = array(
      'description'        => __('An array of field names to include in response. Or "count" to count items. Empty means all fields.'),
      'type'               => 'array',
      'items'              => array(
        'type'           => 'string',
        'sanitize_callback'  => 'sanitize_text_field',
      ),
      'default'            => array(),
    );

    $query_params['offset'] = array(
      'description'        => __('Offset the result set by a specific number of items.'),
      'type'               => 'integer',
      'sanitize_callback'  => 'absint',
    );

    $query_params['limit'] = array(
      'description'        => __('Meximum number of items returned in response.'),
      'type'               => 'integer',
      'sanitize_callback'  => 'absint',
    );

    $query_params['order'] = array(
      'default'            => 'asc',
      'description'        => __('Order sort attribute ascending or descending.'),
      'enum'               => array( 'asc', 'desc' ),
      'type'               => 'string',
    );

    $query_params['orderby'] = array(
      'default'            => 'name',
      'description'        => __('Sort collection by object attribute.'),
      'enum'               => array(
        'id',
        'include',
        'name',
        'updated',
        'created',
        'user_id',
      ),
      'type'               => 'string',
    );

    $query_params['since']  = array(
      'description'        => __( 'Return only activities after this date.' ),
      'type'               => 'string',
      'default'            => '',
      'required'           => false,
      'format'             => 'date-time',
      'sanitize_callback'  => 'sanitize_text_field',
    );
    $query_params['until']  = array(
      'description'        => __( 'Return only activities up to this date.' ),
      'type'               => 'string',
      'default'            => '',
      'required'           => false,
      'format'             => 'date-time',
      'sanitize_callback'  => 'sanitize_text_field',
    );

    $query_params['user_id']  = array(
      'description'        => __( 'An array with one or more user id.' ),
      'type'               => 'array',
      'items'              => array(
        'type'              => 'string',
      ),
      'default'            => array(),
      'required'           => false,
    );

    return $query_params;
  }
}
