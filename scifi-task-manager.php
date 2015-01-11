<?php

/**
 * Plugin Name: scifi Task Manager
 * Plugin URI:  http://wordpress.org/extend/plugins/scifi-task-manager
 * Description: Simple admin dashboard task manager.
 * Author:      Adrian Dimitrov <dimitrov.adrian@gmail.com>
 * Author URI:  http://scifi.bg/opensource/
 * Version:     0.7
 * Text Domain: scifi-task-manager
 * Domain Path: /languages/
 */


/**
 * Localize the plugin.
 */
add_action('plugins_loaded', function() {
  load_plugin_textdomain('scifi-task-manager', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
});

/**
 * Install/Uninstall hooks.
 */
register_activation_hook(__FILE__, '_scifi_task_manager_hook_install');
register_uninstall_hook(__FILE__, '_scifi_task_manager_hook_uninstall');

/**
 * Include plugin helpers.
 */
require 'scifi-task-manager-helpers.php';

/**
 * Register post type, taxonomy, and statuses,
 * and perform init tasks.
 */
add_action('admin_init', function() {
  /**
   * Register post types
   */
  register_post_type('scifi-task-manager', array(
    'label' => __('Tasks', 'scifi-task-manager'),
    'labels' => array(
      'name' => _x('Tasks', 'Post Type General Name', 'scifi-task-manager'),
      'singular_name' => _x('Task', 'Post Type Singular Name', 'scifi-task-manager'),
      'menu_name' => __('Tasks', 'scifi-task-manager'),
      'parent_item_colon' => __('Subtask of', 'scifi-task-manager'),
      'all_items' => __('Tasks', 'scifi-task-manager'),
      'view_item' => __('View task', 'scifi-task-manager'),
      'add_new_item' => __('Add new task', 'scifi-task-manager'),
      'add_new' => __('Add new', 'scifi-task-manager'),
      'edit_item' => __('Edit task', 'scifi-task-manager'),
      'update_item' => __('Update task', 'scifi-task-manager'),
      'search_items' => __('Search in tasks', 'scifi-task-manager'),
      'not_found' => __('No tasks with this criteria', 'scifi-task-manager'),
      'not_found_in_trash' => __('Not found in Trash', 'scifi-task-manager'),
    ),
    'public' => FALSE,
    'show_ui' => TRUE,
    'show_in_menu' => TRUE,
    'show_in_nav_menus' => TRUE,
    'show_in_admin_bar' => TRUE,
    'menu_position' => 10,
    'hierarchical' => TRUE,
    'capability_type' => 'post',
    'query_var' => FALSE,
    'supports' => array('author', 'title', 'editor', 'comments'),
    'can_export' => TRUE,
    'has_archive' => TRUE,
    'menu_icon' => 'dashicons-clipboard',
  ));

  if (get_option('scifi-task-manager_tags')) {
    /**
     * Register taxonomies
     */
    register_taxonomy('scifi-task-manager-tag', 'scifi-task-manager', array(
      'label' => __('Tags', 'scifi-task-manager'),
      'singular_label' => __('Tag', 'scifi-task-manager'),
      'show_in_nav_menus' => FALSE,
      'show_admin_column' => TRUE,
      'show_ui' => TRUE,
      'public' => FALSE,
      'query_var' => FALSE,
      'hierarchical' => TRUE,
    ));
  }

  /**
   * Register statuses
   */
  foreach (scifi_task_manager_get_statuses() as $status_id => $status) {
    $label_count = is_rtl() ? '<span class="count">(%s)</span>' . $status['label'] : $status['label'] . ' <span class="count">(%s)</span>';
    register_post_status($status_id, array(
      'label' => $status['label'],
      'label_count' => _n_noop($label_count, $label_count),
      'public' => TRUE,
      'exclude_from_search' => TRUE,
      'show_in_admin_all_list' => TRUE,
      'show_in_admin_status_list' => TRUE,
      'scifi_task_manager' => TRUE,
      'scifi_task_manager_progress' => $status['progress'],
    ));
  }

  // @menu_position
  if (get_option('scifi-task-manager_menu') == 'ab' && _scifi_task_manager_current_user_can()) {
    add_action('wp_before_admin_bar_render', function() {
      global $wp_admin_bar;
      $args = array(
        'id' => 'scifi-task-manager',
        'title' => sprintf('<span class="ab-item dashicons-clipboard">%s</span>', __('Tasks', 'scifi-task-manager')),
        'href' => admin_url('edit.php?post_type=scifi-task-manager'),
        'group' => FALSE,
      );
      $wp_admin_bar->add_node( $args );
    });
  }

});

/**
 * Menu links
 */
add_action('admin_menu', function() {
  add_options_page(__('scifi Task Manager', 'scifi-task-manager'), __('scifi Task Manager', 'scifi-task-manager'), 'manage_options', 'scifi-task-manager', '_scifi_task_manager_admin_settings');
  $menu_position = get_option('scifi-task-manager_menu', '');
  if (_scifi_task_manager_current_user_can()) {
    if ($menu_position == 'tools') {
      add_management_page(__('scifi Task Manager'), __('Tasks', 'scifi-task-manager'), 'read', 'edit.php?post_type=scifi-task-manager');
    }
    elseif ($menu_position == 'main3') {
      add_menu_page(__('scifi Task Manager'), __('Tasks', 'scifi-task-manager'), 'read', 'edit.php?post_type=scifi-task-manager', NULL, 'dashicons-clipboard', 3);
    }
    elseif ($menu_position == 'main73') {
      add_menu_page(__('scifi Task Manager'), __('Tasks', 'scifi-task-manager'), 'read', 'edit.php?post_type=scifi-task-manager', NULL, 'dashicons-clipboard', 73);
    }
    elseif ($menu_position == '') {
      add_dashboard_page(__('scifi Task Manager'), __('Tasks', 'scifi-task-manager'), 'read', 'edit.php?post_type=scifi-task-manager');
    }
  }

});

/**
 * Add dashboard widget.
 */
add_action('wp_dashboard_setup', function() {
  if (_scifi_task_manager_current_user_can()) {
    wp_add_dashboard_widget('scifi_task_manager_widget', __('Tasks', 'scifi-task-manager'), '_scifi_task_manager_dashboard_widget', '_scifi_task_manager_dashboard_widget_config');
  }
});

/**
 * Admin dashboard UI widget and tweaks
 */
add_action('admin_head', '_scifi_task_manager_cssjs');

/**
 * Override the post view link
 */
add_filter('post_type_link', function($post_link, $post, $leavename, $sample) {
  if ($post->post_type == 'scifi-task-manager') {
    $post_link = get_edit_post_link($post->ID);
  }
  return $post_link;
}, 5, 4);

/**
 * Prepare postdata before saving
 */
add_filter('wp_insert_post_data', function($data, $postattr) {
  return _scifi_task_manager_prepare_post_data($data, $postattr);
}, 999, 2);

/**
 * Mailer action on post updated
 */
add_action('post_updated', function($post_id, $post_after, $post_before) {
  scifi_task_manager_send_mails('update', $post_after, $post_before);
}, 10, 3);

/**
 * Mailer action on post insert
 */
add_action('wp_insert_post', function($post_ID, $post, $update) {
  if (!$update) {
    scifi_task_manager_send_mails('add', $post);
  }
}, 10, 3);

/**
 * Mailer action on comment insert
 */
add_action('wp_insert_comment', function($comment_id, $comment) {
  scifi_task_manager_send_mails('comment', $comment, NULL);
}, 10, 2);

/**
 * Add user profile checkbox to enable/disable the mailer
 */
add_action('personal_options', function($userprofile) {
  $enabled = $userprofile->_scifi_task_manager_recieve_mails === '' ? TRUE : !empty($userprofile->_scifi_task_manager_recieve_mails);
  ?>
  <table class="form-table">
    <tr>
      <th><label for="_scifi_task_manager_recieve_mails"><?php _e('Recieve mails', 'scifi-task-manager')?></label></th>
      <td><input type="checkbox" name="_scifi_task_manager_recieve_mails" value="1" <?php checked(TRUE, $enabled)?> /></td>
    </tr>
  </table>
  <?php
});

/**
 * Update user mailer value
 */
add_action('edit_user_profile_update', function($user_id) {
  update_user_meta($user_id,'_scifi_task_manager_recieve_mails', (empty($_POST['_scifi_task_manager_recieve_mails']) ? '0' : '1'));
});

/**
 * @single
 * Remove noneed metaboxes
 */
add_action('add_meta_boxes_scifi-task-manager', function($post) {
  remove_meta_box('submitdiv', 'scifi-task-manager', 'side');
  remove_meta_box('postcustom', 'scifi-task-manager', 'normal');
  remove_meta_box('slugdiv', 'scifi-task-manager', 'normal');
  remove_meta_box('authordiv', 'scifi-task-manager', 'normal');
  remove_meta_box('commentstatusdiv', 'scifi-task-manager', 'normal');
  remove_meta_box('trackbacksdiv', 'scifi-task-manager', 'normal');
}, 10);

/**
 * @single
 * Adding metaboxes to the scifi-task-manager pt.
 */
add_action('add_meta_boxes_scifi-task-manager', function($post) {

  /**
   * Publish metabox replacement.
   */
  add_meta_box('scifi-task-manager-publish', __('Task details', 'scifi-task-manager'), function() {
    global $post, $action;
    $custom = get_post_custom($post->ID);
    $deadline = !empty($custom['_scifi-task-manager_deadline'][0]) && is_numeric($custom['_scifi-task-manager_deadline'][0]) ? date('Y-m-d', $custom['_scifi-task-manager_deadline'][0]) : '';
    $assignee = !empty($custom['_scifi-task-manager_assignee']) ? $custom['_scifi-task-manager_assignee'] : array(get_current_user_id());
    $statuses = get_post_stati(array('scifi_task_manager' => TRUE), 'object');
    $hierarchical_tasks_qargs = array(
      'post_type' => 'scifi-task-manager',
      'post_status' => array_keys(scifi_task_manager_get_statuses()),
      'exclude_tree' => $post->ID,
      'selected' => $post->post_parent,
      'name' => 'parent_id',
      'show_option_none' => __('(general task)', 'scifi-task-manager'),
      'sort_column' => 'menu_order, post_title',
      'echo' => 0,
    );
    $users = array();
    foreach (get_option('scifi-task-manager_roles', array()) as $role) {
      foreach (get_users(array(
        'role' => $role,
        'orderby' => 'display_name',
        'fields' => array('ID', 'display_name'),
        )) as $user) {
        $users[$user->ID] = $user;
      }
    }

    if ($action != 'edit') {
      $post->menu_order = 50;
    }

    $labels = get_post_type_object('scifi-task-manager')->labels;

    include 'template-post-publish.php';

  }, 'scifi-task-manager', 'side', 'high');

  /**
   * If post have attachments, then load attachment list metabox.
   */
  $attachments_n = count(get_children('post_type=attachment&post_parent=' . $post->ID, ARRAY_N));
  if ($post && !empty($post->ID) && $attachments_n) {
    add_meta_box('scifi-task-manager-attachments', sprintf(__('Attachments (%s)', 'scifi-task-manager'), $attachments_n), function() {
      global $post;
      $attachments = get_children('post_type=attachment&post_parent=' . $post->ID);
      if ($attachments) {
        include 'template-post-attachments.php';
      }
    }, 'scifi-task-manager', 'normal', 'core');
  }

  // Force comments metabox
  if ($post->post_status != 'auto-draft') {
    add_meta_box('commentsdiv', __('Comments'), 'post_comment_meta_box', null, 'normal', 'core');
  }

  /**
   * Add subtasks metabox
   */
  $children_args = array(
    'post_type' => 'scifi-task-manager',
    'post_parent' => $post->ID,
    'post_status' => array_keys(scifi_task_manager_get_statuses()),
  );
  $subtasks_n = count(get_children($children_args, ARRAY_N));
  if ($subtasks_n) {
    add_meta_box('scifi-task-manager-subtasks', sprintf(__('Subtasks (%s)', 'scifi-task-manager'), $subtasks_n), function () {
      global $post;
      $override_config = array(
        'columns' => array(
          'priority' => 1,
          'status' => 1,
          'info' => 0,
        ),
        'show_my_tasks_only' => 0,
        'limit' => 20,
        'time' => '',
        'parent' => $post->ID,
      );
      _scifi_task_manager_dashboard_widget($post, array(), $override_config);
    });
  }

});

/**
 * Output task content
 */
add_action('edit_form_after_title', function() {
  global $post, $action;
  if ($post->post_type !== 'scifi-task-manager') {
    return;
  }
  ?>
  <script>
    (function($) {
      $(document).ready(function() {
        var previewContent = $('<div id="scifi-task-manager-single-task-preview"></div>');
        var previewTab = $('<a id="content-preview" class="preview-content wp-switch-editor"><?php _e('Preview', 'scifi-task-manager')?></a>');
        $(previewContent)
          .appendTo('#wp-content-wrap')
          .hide();
        $(previewTab).appendTo('.wp-editor-tabs');
        $('.wp-switch-editor')
          .bind('click', function() {
            if ($(this).hasClass('preview-content')) {
              $('#wp-content-wrap')
                .removeClass('tmce-active')
                .removeClass('html-active')
                .addClass('preview-active');
              $('#wp-content-editor-container').hide();
              $(previewContent)
                .show()
                .html($('#content').text())
                .html($("#content_ifr").contents().find('body').html());
              $('#wp-content-media-buttons,td#content-resize-handle,#wp-word-count').hide();
            }
            else {
              $(previewContent).hide();
              $('#wp-content-editor-container').show();
              var newClass = 'html-active';
              if ($(this).hasClass('switch-tmce')) {
                newClass = 'tmce-active';
              }
              $('#wp-content-wrap')
                .removeClass('preview-active')
                .addClass(newClass);
              $('#wp-content-media-buttons,td#content-resize-handle,#wp-word-count').show();
            }
          });
          <?php if ($action === 'edit'):?>
            $(previewTab).trigger('click');
          <?php endif?>
      });
    }(jQuery));
  </script>
  <p></p>
  <?php
});

/**
 * @single
 * Override post classes
 */
add_filter('post_class', function($classes, $class, $post_id) {
  $post = get_post($post_id);
  if ($post->post_type == 'scifi-task-manager') {
    $class[] = 'scifi-task-manager-status-' . $post->post_status;
    $class[] = 'scifi-task-manager-priority-' . $post->menu_order;
    $classes = $class;
  }
  return $classes;
}, 10, 3);

/**
 * @single
 * Implements save_post
 */
add_action('save_post_scifi-task-manager', function($post_id, $post, $update) {
  if (empty($post_id) || empty($_POST) || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }
  if (isset($_POST['_inline_edit']) && !wp_verify_nonce($_POST[ '_inline_edit' ], 'inlineeditnonce')) {
    return;
  }
  if (isset($post->post_type) && in_array($post->post_type, array('revision', 'draft', 'auto-draft'))) {
    return;
  }
  if (!empty($_POST['_scifi-task-manager_deadline'])) {
    update_post_meta($post_id, '_scifi-task-manager_deadline', strtotime($_POST['_scifi-task-manager_deadline']));
  }
  delete_post_meta($post_id, '_scifi-task-manager_assignee');
  if (!empty($_POST['_scifi-task-manager_assignee']) && is_array($_POST['_scifi-task-manager_assignee'])) {
    foreach ($_POST['_scifi-task-manager_assignee'] as $assignee_user_id) {
      add_post_meta($post_id, '_scifi-task-manager_assignee', $assignee_user_id, FALSE);
    }
  }
}, 10, 3);

/**
 * @single
 * Manage comment actions
 */
add_filter('comment_row_actions', function($actions, $comment) {
  if (defined('DOING_AJAX') && DOING_AJAX && !empty($_REQUEST['mode']) && $_REQUEST['mode'] == 'single' && !empty($_REQUEST['p']) && !empty($_REQUEST['action']) && ($_REQUEST['action'] == 'get-comments' || $_REQUEST['action'] == 'replyto-comment')) {
    $post = get_post($comment->comment_post_ID);
    if ($post && $post->post_type == 'scifi-task-manager') {
      $actions = array_intersect_key($actions, array_flip(array('approve', 'unapprove', 'reply', 'quickedit', 'trash')));
    }
  }
  return $actions;
}, 10, 2);

/**
 * @list
 * Remove bulk operations
 */
add_filter('bulk_actions-scifi-task-manager', '__return_empty_array');
add_filter('bulk_actions-edit-scifi-task-manager', '__return_empty_array');

/**
 * @list
 * Remove no-need inline operations
 */
add_filter('page_row_actions', function($actions, $post) {
  if ($post->post_type == 'scifi-task-manager') {
    $supported_actions = array('trash');
    $actions = array_intersect_key($actions, array_flip($supported_actions));
  }
  return $actions;
}, 10, 2);

/**
 * @list
 * Override columns
 */
add_filter('manage_edit-scifi-task-manager_columns', function($columns) {
  $new_columns = array();
  $new_columns['menu_order']= __('Priority', 'scifi-task-manager');
  $new_columns['status']  = __('Status', 'scifi-task-manager');
  $new_columns['taskid'] = __('Task ID', 'scifi-task-manager');
  $new_columns = array_merge($new_columns, $columns);
  unset($new_columns['cb']);
  unset($new_columns['comments']);
  unset($new_columns['date']);
  $new_columns['author'] = __('Reported', 'scifi-task-manager');
  $new_columns['assignee']= __('Assignee', 'scifi-task-manager');
  $new_columns['date'] = $columns['date'];
  $new_columns['deadline']= __('Deadline', 'scifi-task-manager');
  $new_columns['comments'] = $columns['comments'];
  return $new_columns;
});

/**
 * @list
 * Make custom columns sortable.
 */
add_filter('manage_edit-scifi-task-manager_sortable_columns', function($sortable_columns) {
  $sortable_columns['status'] = array('status', TRUE);
  $sortable_columns['menu_order'] = array('menu_order', FALSE);
  $sortable_columns['taskid'] = 'taskid';
  $sortable_columns['assignee'] = 'assignee';
  $sortable_columns['deadline'] = array('deadline', TRUE);
  return $sortable_columns;
});

/**
 * @list
 * Add task edit list columns.
 */
add_action('manage_scifi-task-manager_posts_custom_column', function($column_name, $post_id) {
  $output = _scifi_task_manager_format_column($column_name, $post_id, TRUE);
  echo $output ? $output : '&mdash;';
}, 10, 2);

/**
 * @list
 * Add custom filters widgets.
 */
add_action('restrict_manage_posts', function() {
  global $typenow, $wp_query;
  if ($typenow !== 'scifi-task-manager') {
    return;
  }
  $users = array();
  foreach (get_option('scifi-task-manager_roles', array()) as $role) {
    $users += get_users(array(
      'role' => $role,
      'orderby' => 'display_name',
      'fields' => array('ID', 'display_name'),
    ));
  }
  $tags_taxonomy = get_taxonomy('scifi-task-manager-tag');
  if (get_option('scifi-task-manager_tags')) {
    $tags_terms = get_terms($tags_taxonomy->name, 'pad_counts=1&hide_empty=0&hierarchical=1');
  }
  else {
    $tags_terms = array();
  }
  include 'template-post-list-filters.php';
});

/**
 * @list
 * Make admin list filters and sortables to work.
 */
add_filter('parse_query', function($query) {
  global $pagenow;
  if ($pagenow == 'edit.php' && !empty($query->query_vars['post_type']) && $query->query_vars['post_type']) {

    if (!empty($_GET['assignee'])) {
      $query->set('meta_key', '_scifi-task-manager_assignee');
      $query->set('meta_value', $_GET['assignee']);
    }

    if (!empty($_GET['parent_id'])) {
      $query->set('post_parent', $_GET['parent_id']);
    }

    if (!empty($_GET['filter_post_status'])) {
      $query->set('post_status', $_GET['filter_post_status']);
    }
  }
  return $query;
});
