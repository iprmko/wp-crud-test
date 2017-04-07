<?php
/**
* Clean template for display items
*
* @package
* @subpackage
* @since
*/
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!--[if lt IE 9]>
  <script src="<?php echo esc_url(get_template_directory_uri()); ?>/js/html5.js"></script>
  <![endif]-->
  <?php wp_head(); ?>

  <style media="screen">
  button {
    height: 40px;
    padding: 5px;
  }
  button,
  tr.selected {
    background-color: #0085ba;
    color: #fff;
  }
  button[disabled]{
    background-color: gray;
    cursor: not-allowed;
  }
  button:hover{
    background-color: #3db4e4;
  }
  .site-content {
    padding: 20px;
  }
  thead {
    background-color: #333;
    color: white;
  }
  input[type=checkbox] {
    margin: 0px 5px;
  }
  </style>
</head>
<body>
  <div class="site-content">

    <div id="crud-items"></div>
    <section>
      <button id="crud-create-button">Create new item</button>
      <button id="crud-read-button">Refresh items list</button>
      <button id="crud-update-button" disabled="disabled">Update selected item</button>
      <button id="crud-delete-button" disabled="disabled">Delete checked items</button>
      <input type="text" id="crud-query-params" placeholder="Query optional params es:since=2017-04-05 17:13:01"/></td>
      <table>
        <tr>
          <td>Name</td>
          <td><input type="text" id="item-name"/></td>
        </tr>
        <tr>
          <td>Data</td>
          <td><textarea id="item-data" rows="8" cols="80"></textarea></td>
        </tr>
      </table>
    </section>

  </div>
  <?php get_footer(); ?>
</body>
