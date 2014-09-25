<?php 

/**
 * Hook on install
 */
function _scifi_task_manager_hook_install() {
  $roles = array('administrator', 'editor', 'author', 'contributor');
  add_option('scifi-task-manager_roles', $roles);
  add_option('scifi-task-manager_menu', 'main3');
  add_option('scifi-task-manager_tags', '1');
}

/**
 * Hook on uninstall
 */
function _scifi_task_manager_hook_uninstall() {
  delete_option('scifi-task-manager_roles');
  delete_option('scifi-task-manager_menu_item');
}

/**
 * Check if current user have access to scifi task manager
 *
 * @return bool
 */
function _scifi_task_manager_current_user_can() {
  return array_intersect(wp_get_current_user()->roles, get_option('scifi-task-manager_roles', array())) ? TRUE : FALSE;
}

/**
 * Get registered priorities or just a one
 *
 * @param $priority
 *
 * @return array|string
 */
function scifi_task_manager_get_priorities($priority = 'all') {
  $labels = apply_filters('scifi-task-manager-priorities', array());
  if ($priority === 'all') {
    return $labels;
  }
  else {
    return empty($labels[$priority]) ? __('Normal', 'scifi-task-manager') : $labels[$priority];
  }
}

/**
 * Get registered statuses or just a one
 *
 * @param $status
 *
 * @return array|object
 */
function scifi_task_manager_get_statuses($status = 'all') {
  $statuses = apply_filters('scifi-task-manager-statuses', array());
  if ($status === 'all') {
    return $statuses;
  }
  else {
    return empty($statuses[$status]) ? NULL : $statuses[$status];
  }
}

/**
 * Prepare suitable color for given number
 *
 * @param $n
 *
 * @return string
 */
function scifi_task_manager_color($n) {
  $r = (150*$n+105)/100;
  $g = (205*(100-$n)+50)/100;
  return '#' . str_pad(dechex($r), 2, "0", STR_PAD_LEFT) . str_pad(dechex($g), 2, "0", STR_PAD_LEFT) . '33';
}

/**
 * Prepare task postdata
 *
 * @param $post
 *
 * @return array|object
 */
function _scifi_task_manager_prepare_post_data($post) {
  $returnobj = is_object($post);
  if (!$returnobj) {
    $post = (object) $post;
  }
  if ($post->post_type === 'scifi-task-manager') {
    // Do stuff.
    if (!$post->post_title && !empty($post->ID)) {
      $post->post_title = $post->ID;
    }
    if ($post->post_status !== 'auto-draft' && $post->post_status !== 'draft') {
      $post->post_name = '#' . preg_replace('#[^\w\d\-\_\/]+#i', '', substr(strtoupper($post->post_name), 0, 31));
    }
    $post->comment_status = 'open';
    $post->ping_status = 'close';
    // Return
  }
  return $returnobj ? $post : (array) $post;
}

/**
 * Output formated value for given task data column
 *
 * @param $column_name
 * @param $post_id
 * @param $return
 *
 * @return string
 */
function _scifi_task_manager_format_column($column_name, $post_id, $return = FALSE) {
  $post = get_post($post_id);
  $custom = get_post_custom($post_id);
  $assignee = !empty($custom['_scifi-task-manager_assignee']) ? $custom['_scifi-task-manager_assignee'] : array();
  $deadline = !empty($custom['_scifi-task-manager_deadline'][0]) ? $custom['_scifi-task-manager_deadline'][0] : '';
  $output = '';

  if ($column_name == 'status') {
    $status = scifi_task_manager_get_statuses($post->post_status);
    if ($status) {
      $output = $status->label;
    }
  }
  elseif ($column_name == 'priority' || $column_name == 'menu_order') {
    $output = scifi_task_manager_get_priorities($post->menu_order);
  }
  elseif ($column_name == 'taskid') {
    $output = $post->post_name ? $post->post_name : $post->ID;
  }
  elseif ($column_name == 'assignee') {
    $list = array();
    foreach ($assignee as $user_id) {
      $user = get_userdata($user_id);
      $list[] = $user->display_name;
    }
    if ($list) {
      $output = implode(', ', $list);
    }
  }
  elseif ($column_name == 'reporter' || $column_name == 'author') {
    $user = get_userdata($post->post_author);
    if ($user) {
      $output = $user->display_name;
    }
  }
  elseif ($column_name == 'deadline') {
    if ($deadline && is_numeric($deadline)) {
      $output = date(get_option('date_format', 'U'), $deadline);
    }
  }
  elseif ($column_name == 'attachments') {
    $output = count(get_children('post_type=attachment&post_parent=' . $post_id, ARRAY_N));
  }

  if ($return) {
    return $output;
  }
  else {
    echo $output;
  }
}

/**
 * Dashboard widget
 *
 * @param $post
 * @param $callback_args
 * @param $override_config
 */
function _scifi_task_manager_dashboard_widget($post, $callback_args, $override_config = array()) {
  $config = array_merge(scifi_task_manager_dashboard_widget_get_config(), $override_config);
  $posts = scifi_task_manager_dashboard_widget_get_tasks($config);
  include 'template-admin-widget.php';
}

/**
 * Get all tasks according to the widget configuration.
 *
 * @param $override_config
 *
 * @return array
 */
function scifi_task_manager_dashboard_widget_get_tasks($config = array()) {
  global $wpdb;

  $args = array(
    'post_type' => 'scifi-task-manager',
    'post_status' => array_keys(scifi_task_manager_get_statuses()),
    'numberposts' => -1,
    'orderby' => 'menu_order',
    'order' => 'DESC',
  );

  if (!empty($config['orderby'])) {
    if ($args['orderby'] == 'deadline') {
      $args['meta_value'] = '_scifi-task-manager_deadline';
      $args['meta_value_num'] = TRUE;
    }
    else {
      $args['orderby'] = $config['orderby'];
    }
  }

  if (!empty($config['limit'])) {
    $args['numberposts'] = $config['limit'];
  }

  if (!empty($config['show_my_tasks_only'])) {
    $assigned_to_me = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_scifi-task-manager_assignee' AND meta_value = %s;", get_current_user_id()));
    $from_me = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'scifi-task-manager' AND post_status NOT IN ('auto-draft', 'draft') AND post_author = %s;", get_current_user_id()));
    $args['post__in'] = array_unique(array_merge($assigned_to_me, $from_me));
  }

  if (!empty($config['time'])) {
    $args['year'] = date('Y');
    switch ($config['time']) {
      case 'today':
        $args['day'] = date('d');
      case 'week':
        $args['w'] = date('W');
      case 'month':
        $args['monthnum'] = date('n');
    }
  }

  if (!empty($config['parent'])) {
    $args['post_parent'] = $config['parent'];
  }

  $posts = get_posts($args);
  return $posts;
}

/**
 * Get dashboard widget configuration.
 *
 * @return array
 */
function scifi_task_manager_dashboard_widget_get_config() {
  $config = get_user_meta(get_current_user_id(), '_scifi_task_manager_admin_widget', TRUE);
  if (!$config) {
    $config = array(
      'columns' => array(
        'priority' => 1,
        'status' => 1,
        'info' => 1,
      ),
      'show_my_tasks_only' => 1,
      'limit' => 20,
      'time' => '',
      'orderby' => '',
      'parent' => '',
    );
  }
  return $config;
}

/**
 * Build widget configuration
 *
 * @param $post
 * @param $callback_args
 */
function _scifi_task_manager_dashboard_widget_config($post, $callback_args) {

  if (empty($_POST['submit'])) {
    $config = scifi_task_manager_dashboard_widget_get_config();
    echo '
      <fieldset>
        <p>
          <strong> ' . __('Columns', 'scifi-task-manager') . '</strong>
        </p>
        <p>
          <input type="checkbox" name="columns[priority]" value="priority" id="scifi-task-manager-columns-col-priority" ' . checked(TRUE, !empty($config['columns']['priority']), FALSE) . ' />
          <label for="scifi-task-manager-columns-col-priority">
            ' . __('Priority', 'scifi-task-manager') . '
          </label>
        </p>
        <p>
          <input type="checkbox" name="columns[status]" value="status" id="scifi-task-manager-columns-col-status" ' . checked(TRUE, !empty($config['columns']['status']), FALSE) . ' />
          <label for="scifi-task-manager-columns-col-status">
            ' . __('Status', 'scifi-task-manager') . '
          </label>
        </p>
        <p>
          <input type="checkbox" name="columns[info]" value="info" id="scifi-task-manager-columns-col-info" ' . checked(TRUE, !empty($config['columns']['info']), FALSE) . ' />
          <label for="scifi-task-manager-columns-col-info">
            ' . __('Info', 'scifi-task-manager') . '
          </label>
        </p>
        <p>
          <label for="scifi-task-manager-orderby">
            ' . __('Order by', 'scifi-task-manager') . '
          </label>
          <select id="scifi-task-manager-orderby" name="orderby">
            <option value="">' . __('Default', 'scifi-task-manager') . '</option>
            <option value="status" ' . selected('status', $config['orderby'], FALSE) . '>' . __('Status', 'scifi-task-manager'). '</option>
            <option value="menu_order" ' . selected('menu_order', $config['orderby'], FALSE) . '>' . __('Priority', 'scifi-task-manager'). '</option>
            <option value="date" ' . selected('date', $config['orderby'], FALSE) . '>' . __('Date', 'scifi-task-manager'). '</option>
            <option value="deadline" ' . selected('deadline', $config['orderby'], FALSE) . '>' . __('Deadline', 'scifi-task-manager'). '</option>
          </select>
        </p>
      </fieldset>
      <fieldset>
        <p>
          <strong> ' . __('Other settings', 'scifi-task-manager') . '</strong>
        </p>
        <p>
          <input type="checkbox" name="show_my_tasks_only" value="1" id="scifi-task-manager-show-my" ' . checked(TRUE, !empty($config['show_my_tasks_only']), FALSE) . ' />
          <label for="scifi-task-manager-show-my">
            ' . __('Show my tasks only', 'scifi-task-manager') . '
          </label>
        </p>
        <p>
          <label for="scifi-task-manager-limit">
            ' . __('Limit', 'scifi-task-manager') . '
          </label>
          <input type="number" name="limit" min="5" max="999" step="1" value="' . esc_attr($config['limit']) . '" id="scifi-task-manager-limit" />
        </p>
        <p>
          <label for="scifi-task-manager-time">
            ' . __('Time filter', 'scifi-task-manager') . '
          </label>
          <select id="scifi-task-manager-time" name="time">
            <option value="">' . __('Any', 'scifi-task-manager') . '</option>
            <option value="today" ' . selected('today', $config['time'], FALSE) . '>' . __('Today', 'scifi-task-manager'). '</option>
            <option value="week" ' . selected('week', $config['time'], FALSE) . '>' . __('Week', 'scifi-task-manager'). '</option>
            <option value="month" ' . selected('month', $config['time'], FALSE) . '>' . __('Month', 'scifi-task-manager'). '</option>
            <option value="year" ' . selected('year', $config['time'], FALSE) . '>' . __('Year', 'scifi-task-manager'). '</option>
          </select>
        </p>
      </fieldset>
      ';
  }
  else {
    update_user_meta(get_current_user_id(), '_scifi_task_manager_admin_widget', $_POST);
  }
}

/**
 * Admin settings form
 */
function _scifi_task_manager_admin_settings() {

  if (!empty($_POST['scifi-task-manager-admin-settings']) && wp_verify_nonce($_POST['scifi-task-manager-admin-settings'], 'scifi-task-manager-admin-settings')) {
    update_option('scifi-task-manager_menu', $_POST['scifi-task-manager_menu']);
    update_option('scifi-task-manager_roles', $_POST['scifi-task-manager_roles']);
    update_option('scifi-task-manager_tags', !empty($_POST['scifi-task-manager_tags']));
  }

  $menu_position = get_option('scifi-task-manager_menu');
  global $wp_roles;
  $roles = array_flip(get_option('scifi-task-manager_roles', array()));

  ?>
  <div class="wrap">
    <h2><?php _e('scifi Task Manager settings', 'scifi-task-manager')?></h2>

    <form method="post">
      <?php wp_nonce_field('scifi-task-manager-admin-settings', 'scifi-task-manager-admin-settings')?>
      <table class="form-table">

        <tr>
          <th>
            <label for="scifi-task-manager-menu">
              <?php _e('Menu position', 'scifi-task-manager')?>
            </label>
          </th>
          <td>
            <select name="scifi-task-manager_menu">
              <option value=""><?php _e('Dashboard', 'scifi-task-manager')?></option>
              <option value="main3" <?php selected('main3', $menu_position)?>><?php _e('Main Menu (top)', 'scifi-task-manager')?></option>
              <option value="main73" <?php selected('main73', $menu_position)?>><?php _e('Main Menu (auto)', 'scifi-task-manager')?></option>
              <option value="ab" <?php selected('ab', $menu_position)?>><?php _e('Admin Bar', 'scifi-task-manager')?></option>
              <option value="tools" <?php selected('tools', $menu_position)?>><?php _e('Tools', 'scifi-task-manager')?></option>
            </select>
          </td>
        </tr>

        <tr>
          <th>
            <?php _e('Tags support', 'scifi-task-manager')?>
          </th>
          <td>
            <p>
              <input type="checkbox" id="scifi-task-manager_tags" name="scifi-task-manager_tags" value="1" <?php checked(get_option('scifi-task-manager_tags'), 1)?> />
              <label for="scifi-task-manager_tags">
                <?php _e('Enable', 'scifi-task-manager')?>
              </label>
            </p>
            <?php if (get_option('scifi-task-manager_tags')):?>
            <p>
              <a href="<?php echo admin_url('edit-tags.php?taxonomy=scifi-task-manager-tag')?>">
                <?php _e('Manage tags', 'scifi-task-manager')?>
              </a>
            </p>
            <?php endif?>
          </td>
        </tr>

        <tr>
          <th>
            <label for="scifi-task-manager-roles">
              <?php _e('User roles', 'scifi-task-manager')?>
            </label>
          </th>
          <td>
            <?php foreach ($wp_roles->roles as $role_id => $role_data):?>
              <p>
                <input type="checkbox" id="scifi-task-manager_roles-<?php echo esc_attr($role_id)?>" name="scifi-task-manager_roles[]" value="<?php echo esc_attr($role_id)?>" <?php checked(isset($roles[$role_id], $role_id), TRUE)?> />
                <label for="scifi-task-manager_roles-<?php echo esc_attr($role_id)?>">
                  <?php echo $role_data['name']?>
                </label>
              </p>
            <?php endforeach?>
          </td>
        </tr>

      </table>
      
      <?php echo submit_button()?>
    </form>
  </div>
  <?php
}