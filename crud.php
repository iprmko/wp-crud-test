<?php
/**
*	Crud: Virtual page for the crud web-client
*/

function crud_test_run()
{
  add_filter('query_vars', 'crud_virtualpage_query_vars');

  add_filter('template_include', 'crud_virtualpage_template_include');

  /**
  * Setup JavaScript
  */
  add_action('wp_enqueue_scripts', function () {
    //load script
    wp_enqueue_script('crud-test-script', plugin_dir_url(__FILE__) . 'crud-test.js', array( 'jquery' ));

    //localize data for script
    wp_localize_script('crud-test-script', 'CRUD_PARAMS', array(
      'root' => esc_url_raw(rest_url()),
      'nonce' => wp_create_nonce('wp_rest'),
      'current_user_id' => get_current_user_id(),
      'home_url'  => home_url(),
      )
    );
  });
}
function crud_test_activate()
{
  /**
  * Add redirects to point desired virtual page paths to the new
  * index.php?virtualpage=name destination.
  *
  * After this code is updated, the permalink settings in the administration
  * interface must be saved before they will take effect. This can be done
  * programmatically as well, using flush_rewrite_rules() triggered on theme
  * or plugin install, update, or removal.
  */
  add_rewrite_tag('%virtualpage%', '([^&]+)');
  add_rewrite_rule(
    'crud_test/?$',
    'index.php?virtualpage=crud_test',
    'top'
  );
  flush_rewrite_rules();
}
function crud_test_deactivate()
{
  //todo
}

/**
* Create a query variable addition for the pages.
*   This means that WordPress will recognize index.php?virtualpage=name
*/
function crud_virtualpage_query_vars($vars)
{
  $vars[] = 'virtualpage';
  return $vars;
}

/**
* Assign templates to the virtual pages.
*/
function crud_virtualpage_template_include($template)
{
  global $wp_query;
  $new_template = '';
  if (array_key_exists('virtualpage', $wp_query->query_vars)) {
    switch ($wp_query->query_vars['virtualpage']) {
      case 'crud_test':
      $new_template = plugin_dir_path(__FILE__) . 'crud-test.php';
      break;
    }

    if ($new_template != '') {
      return $new_template;
    } else {
      // This is not a valid virtualpage value,
      // so set the header and template
      // for a 404 page.
      $wp_query->set_404();
      status_header(404);
      return get_404_template();
    }
  }

  return $template;
}
