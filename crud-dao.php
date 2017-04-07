<?php
/*
Plugin Name:
Version: 1.0
Description:
Author:
Author URI:
*/

/**
* Store our table name in $wpdb with correct prefix
* Prefix will vary between sites
* @since 1.0
*/
class Crud_dao
{

  /**
  * Table name.
  *
  * @access public
  * @var string
  */
  public static $table_name='crud_table';

  private static function get_table_name()
  {
    global $wpdb;
    return  $wpdb->prefix . Crud_dao::$table_name;
  }

  public static function activate()
  {
    return Crud_dao::create_table();
  }

  public static function deactivate()
  {
    return Crud_dao::drop_table();
  }

  /**
  * Creates our table
  */
  public static function drop_table()
  {
    global $wpdb;
    global $charset_collate;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $full_table_name = Crud_dao::get_table_name();

    return $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
  }

  /**
  * Drop our table
  */
  public static function create_table()
  {
    global $wpdb;
    global $charset_collate;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $full_table_name = Crud_dao::get_table_name();

    $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");

    $sql_create_table = "CREATE TABLE {$full_table_name} (
      id bigint(20) unsigned NOT NULL auto_increment,
      user_id bigint(20) unsigned NOT NULL default '0',
      name varchar(30) NOT NULL default 'updated',
      data varchar(30) NOT NULL default '{}',
      created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY  (id),
      KEY abc (user_id)
    ) $charset_collate; ";
    return dbDelta($sql_create_table);
  }

  /**
  * Create an univoke table name
  * @return string table name
  */
  protected function get_crud_table_columns()
  {
    return array(
      'id'      => '%d',
      'name'    => '%s',
      'created' => '%s',
      'update'  => '%s',
      'data'    => '%s',
    );
  }

  /**
  * Inserts an item into the database
  *@param $data array An array of key => value pairs to be inserted
  *@return int The id of the created item. Or WP_Error or false on failure.
  */
  public function insert_item($item=array())
  {
    global $wpdb;
    $full_table_name = Crud_dao::get_table_name();
    $now = current_time('timestamp');
    //Convert activity date from local timestamp to GMT mysql format
    $now = date_i18n('Y-m-d H:i:s', $now, true);

    //Set default values
    $item = wp_parse_args($item, array(
      'user_id'   => get_current_user_id(),
      'id'        => -1,
      'updated'   => ''
    ));

    //Imports query parameters into into the local symbol table
    extract($item);

    // Are we updating or creating?
    $update = ($id>0);

    //Detect conflicts
    if ($update) {
      if(empty($updated) || $updated == '')
        return new WP_Error('db_error', __('During update we need to send the last update timestamp.'));
      $db_items = $this->query_item(array(
        'id' => $id
      ));
      if (!is_array($db_items) || empty($db_items[0])) {
        return new WP_Error('db_error', __('Requested item could not be found.'));
      }
      $db_item = $db_items[0];
      $last_update =  $db_item->updated;

      if ($last_update !== $updated) {
        return new WP_Error('database_collision', __('Update conflicts with a previous modification.'));
      } else {
        // Update timestamp
        $item['updated'] = $now;
      }
    }
    //Initialise column format array
    $column_formats = $this->get_crud_table_columns();
    //Force fields to lower case
    $item = array_change_key_case($item);
    //White list columns
    $item = array_intersect_key($item, $column_formats);
    //Reorder $column_formats to match the order of columns given in $item
    $item_keys = array_keys($item);
    $column_formats = array_merge(array_flip($item_keys), $column_formats);

    if ($update) {
      $wpdb->update($full_table_name, $item, array('ID'=>$id), $column_formats);
    } else {
      $wpdb->insert($full_table_name, $item, $column_formats);
    }

    if (false === $wpdb) {
      return false;
    } else {
      return $id;
    }
  }

  /**
  * Retrieves the item from the database matching $query.
  * $query is an array which can contain the following keys:
  *
  * 'fields' - an array of columns to include in returned roles. Or 'count' to count rows. Default: empty (all fields).
  * 'orderby' - datetime, user_id or ID. Default: datetime.
  * 'order' - asc or desc
  * 'user_id' - user ID to match, or an array of user IDs
  * 'since' - timestamp. Return only activities after this date. Default false, no restriction.
  * 'until' - timestamp. Return only activities up to this date. Default false, no restriction.
  *
  *@param $query Query array
  *@return array Array of matching items. False on error.
  */
  public function query_item($query=array())
  {
    global $wpdb;
    $full_table_name = Crud_dao::get_table_name();
    /* Parse defaults */
    $defaults = array(
      'fields'=>array(),
      'orderby'=>'id',
      'order'=>'desc',
      'user_id'=>false,
      'id'=>false,
      'since'=>false,
      'until'=>false,
      'limit'=> PHP_INT_MAX,
      'offset'=>0,
    );
    $query = wp_parse_args($query, $defaults);

    /* Form a cache key from the query */
    $cache_key = 'crud_items:'.md5(serialize($query));
    $cache = wp_cache_get($cache_key);
    if (false !== $cache) {
      $cache = apply_filters('query_item', $cache, $query);
      return $cache;
    }

    //Imports query parameters into into the local symbol table
    extract($query);

    /*--- SQL Select ---*/
    //Whitelist of allowed fields
    $allowed_fields = $this->get_crud_table_columns();
    if (is_array($fields)) {
      //Convert fields to lowercase (as our column names are all lower case - see part 1)
      $fields = array_map('strtolower', $fields);
      //Sanitize by white listing
      $fields = array_intersect($fields, $allowed_fields);
    } else {
      $fields = strtolower($fields);
    }

    //Return only selected fields. Empty is interpreted as all
    if (empty($fields)) {
      $select_sql = "SELECT* FROM {$full_table_name}";
    } else {
      $select_sql = "SELECT ".implode(',', $fields)." FROM {$full_table_name}";
    }

    /*--- SQL Where ---*/
    //Initialise WHERE
    $where_sql = 'WHERE 1=1';

    if ($id) {
      $where_sql .=  $wpdb->prepare(' AND ID=%d', $id);
    }

    if (!empty($user_id)) {
      //Force $user_id to be an array
      if (!is_array($user_id)) {
        $user_id = array($user_id);
      }
      $user_id = array_map('absint', $user_id); //Cast as positive integers
      $user_id__in = implode(',', $user_id);
      $where_sql .=  " AND user_id IN($user_id__in)";
    }

    if ($since) {
      $where_sql .=  $wpdb->prepare(' AND updated >= %s', $since);
    }
    if ($until) {
      $where_sql .=  $wpdb->prepare(' AND updated <= %s', $until);
    }

    /*--- SQL Order ---*/
    if (!empty($orderby)) {
      //Whitelist order
      $order = strtoupper($order);
      $order = ('ASC' == $order ? 'ASC' : 'DESC');

      $order_sql = "ORDER BY $orderby $order";
    }

    /*--- SQL Limit ---*/
    $limit_sql = '';
    if ($limit !== PHP_INT_MAX || $offset !== 0) {
      $limit_sql =  "LIMIT $limit OFFSET $offset";
    }

    /* Form SQL statement */
    $sql = "$select_sql $where_sql $order_sql $limit_sql";

    /* Perform query */
    $items = $wpdb->get_results($sql);
    /* Add to cache and filter */
    wp_cache_add($cache_key, $items, 24*60*60);
    $items = apply_filters('query_item', $items, $query);
    return $items;
  }

  /**
  * Inserts a item into the database
  *
  *@param $data array An array of key => value pairs to be inserted
  *@return int The item ID of the created item. Or WP_Error or false on failure.
  */
  public function delete_item($data=array())
  {
    global $wpdb;
    $full_table_name = Crud_dao::get_table_name();
    //Set default values
    $data = wp_parse_args($data, array(
      'user_id'=> get_current_user_id(),
      'updated'=> current_time('updated'),
    ));

    $id= $data['id'];

    do_action('crud_delete_item', $id);
    $sql = $wpdb->prepare("DELETE from {$full_table_name} WHERE ID = %d", $id);
    if (!$wpdb->query($sql)) {
      return false;
    }

    do_action('crud_deleted_item', $id);
    return $id;
  }
}
