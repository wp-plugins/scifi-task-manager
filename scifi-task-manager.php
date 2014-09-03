<?php

/**
 * Plugin Name: scifi Task Manager
 * Plugin URI:  http://wordpress.org/extend/plugins/scifi-task-manager
 * Description: Simple admin dashboard task manager.
 * Author:      Adrian Dimitrov <dimitrov.adrian@gmail.com>
 * Author URI:  http://scifi.bg/opensource/
 * Version:     0.2
 * Text Domain: scifi-task-manager
 * Domain Path: /languages/
 */

define('SCIFI_TASK_MANAGER_ROLE', '');

/**
 * Localize the plugin.
 */
add_action('plugins_loaded', function() {
  load_plugin_textdomain('scifi-task-manager', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
});

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
    'menu_position' => 5,
    'public' => FALSE,
    'show_ui' => TRUE,
    'show_in_menu' => TRUE,
    'show_in_nav_menus' => TRUE,
    'show_in_admin_bar' => TRUE,
    'hierarchical' => TRUE,
    'capability_type' => 'post',
    'query_var' => FALSE,
    'supports' => array('author', 'title', 'editor', 'comments'),
    'can_export' => TRUE,
    'has_archive' => TRUE,
    'menu_icon' => 'dashicons-portfolio',
  ));

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


  /**
   * Register statuses
   */
  foreach (scifi_task_manager_get_statuses() as $status_id => $status) {
    register_post_status($status_id, array(
      'label' => $status->label,
      'public' => TRUE,
      'exclude_from_search' => TRUE,
      'show_in_admin_all_list' => FALSE,
      'show_in_admin_status_list' => FALSE,
      'scifi_task_manager' => TRUE,
      'scifi_task_manager_progress' => $status->progress,
    ));
  }

  /**
   * Add UI links
   */
  add_dashboard_page(__('Tasks', 'scifi-task-manager'), __('Tasks', 'scifi-task-manager'), 'read', 'edit.php?post_type=scifi-task-manager');

});

/**
 * Admin dashboard UI widget and tweaks
 */
add_action('admin_head', function() {
  ?>
  <style>
    .preview-active .preview-content { background: #fff; color: #555; height: 20px; border-bottom: none; }
    #scifi-task-manager-single-task-preview { background: #fff; padding: 2%; border: 1px solid #dedede; clear: both; width: 95.8%; float: left; margin-bottom: 1em; }
    #scifi_task_manager_widget p.info { color: #AAA; font-size: 1.2em; font-weight: bold; text-align: center; padding-bottom: 1em; }
    .dashboard-widget-control-form fieldset { display: inline-block; vertical-align: top; margin: 11px; }
    .dashboard-widget-control-form,
    #scifi-task-manager-publish.postbox .inside #minor-publishing { margin: 10px; }
    body.post-type-scifi-task-manager .actions.bulkactions,
    #scifi-task-manager-attachments.postbox .inside,
    #scifi-task-manager-publish.postbox .inside,
    #scifi-task-manager-subtasks.postbox .inside,
    #dashboard-widgets #scifi_task_manager_widget .inside { margin: 0; padding: 0; }
    #scifi-task-manager-attachments.postbox .inside .wp-list-table,
    #scifi-task-manager-subtasks.postbox .inside .wp-list-table,
    #dashboard-widgets #scifi_task_manager_widget .wp-list-table { border: 0; }
    #scifi_task_manager_widget td p { margin: 0; }
    /* General colorization ans styling */
    .wp-list-table tbody .column-status,
    .wp-list-table tbody .column-menu_order { text-align: center; vertical-align: middle; font-size: .8em; font-weight: bold; text-shadow: 0 0 6px #333; color: #fff; }
    .wp-list-table .column-status,
    .wp-list-table .column-menu_order { width: 80px; }
    <?php
    foreach (scifi_task_manager_get_priorities('all') as $priority_number => $priority) {
      echo ".scifi-task-manager-priority-{$priority_number} .column-menu_order {background: " . scifi_task_manager_color($priority_number) . ';} ';
    }
    foreach (scifi_task_manager_get_statuses('all') as $status_name => $status) {
      echo ".scifi-task-manager-status-{$status_name} .column-status {background: " . scifi_task_manager_color($status->progress) . ';} ';
    }
    ?>
  </style>
<?php
});

/**
 * Add dashboard widget.
 */
add_action('wp_dashboard_setup', function() {
  wp_add_dashboard_widget('scifi_task_manager_widget', __('Tasks', 'scifi-task-manager'), '_scifi_task_manager_dashboard_widget', '_scifi_task_manager_dashboard_widget_config');
});

/**
 * Init priorities
 */
add_filter('scifi-task-manager-priorities', function($priorities = array()) {
  $priorities[10] = __('Trivial', 'scifi-task-manager');
  $priorities[25] = __('Low', 'scifi-task-manager');
  $priorities[50] = __('Normal', 'scifi-task-manager');
  $priorities[75] = __('High', 'scifi-task-manager');
  $priorities[90] = __('Critical', 'scifi-task-manager');
  return $priorities;
});

/**
 * Init statuses
 */
add_filter('scifi-task-manager-statuses', function($statuses) {
  $statuses['scifitm-pending'] = (object) array(
    'label' => __('Pending', 'scifi-task-manager'),
    'progress' => 80
  );
  $statuses['scifitm-rejected'] = (object) array(
    'label' => __('Rejected', 'scifi-task-manager'),
    'progress' => 70
  );
  $statuses['scifitm-hold'] = (object) array(
    'label' => __('Hold', 'scifi-task-manager'),
    'progress' => 60,
  );
  $statuses['scifitm-inprogress'] = (object) array(
    'label' => __('In Progress', 'scifi-task-manager'),
    'progress' => 40,
  );
  $statuses['scifitm-waitreview'] = (object) array(
    'label' => __('Awaiting review', 'scifi-task-manager'),
    'progress' => 40,
  );
  $statuses['scifitm-inreview'] = (object) array(
    'label' => __('In Review', 'scifi-task-manager'),
    'progress' => 30
  );
  $statuses['scifitm-resolved'] = (object) array(
    'label' => __('Resolved', 'scifi-task-manager'),
    'progress' => 0,
  );
  $statuses['scifitm-completed'] = (object) array(
    'label' => __('Completed', 'scifi-task-manager'),
    'progress' => 0,
  );
  return $statuses;
});

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
  return _scifi_task_manager_prepare_post_data($data);
}, 999, 2);

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
    $users = get_users(array(
      'role' => SCIFI_TASK_MANAGER_ROLE,
      'orderby' => 'display_name',
      'fields' => array('ID', 'display_name'),
    ));

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
              $('#post-status-info').hide();
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
              $('#post-status-info').show();
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
  if (isset($post->post_type) && $post->post_type == 'revision') {
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
 * @list
 * Remove bulk operations
 */
add_filter('bulk_actions-scifi-task-manager', '__return_empty_array');
add_filter('bulk_actions-edit-scifi-task-manager', '__return_empty_array');

/**
 * @list
 * Remove noneed inline operations
 */
add_filter('page_row_actions', function($actions, $post) {
  unset($actions['inline hide-if-no-js']);
  unset($actions['pgcache_purge']);
  unset($actions['edit']);
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
  $sortable_columns['status'] = 'status';
  $sortable_columns['menu_order'] = array('menu_order', TRUE);
  $sortable_columns['taskid'] = 'taskid';
  $sortable_columns['assignee'] = 'assignee';
  $sortable_columns['deadline'] = 'deadline';
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
 * Add link for tags management.
 */
add_filter('views_edit-scifi-task-manager', function($views) {
  $views['scifi-task-manager-tag-taxonomy'] = '<a href="edit-tags.php?taxonomy=scifi-task-manager-tag">' . __('Edit tags', 'scifi-task-manager') . '</a>';
  return $views;
});

/**
 * @list
 * Add custom filters widgets.
 */
add_action('restrict_manage_posts', function() {
  global $typenow, $wp_query;
  if ($typenow !== 'scifi-task-manager') {
    return;
  }
  $users = get_users(array(
    'role' => SCIFI_TASK_MANAGER_ROLE,
    'orderby' => 'display_name',
    'fields' => array('ID', 'display_name'),
  ));
  $tags_taxonomy = get_taxonomy('scifi-task-manager-tag');
  $tags_terms = get_terms($tags_taxonomy->name, 'pad_counts=1&hide_empty=0&hierarchical=1');
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
  }
  return $query;
});