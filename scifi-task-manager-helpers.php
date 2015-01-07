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
 * @param $label
 *
 * @return array|string
 */
function scifi_task_manager_get_priorities($priority = 'all', $label = FALSE) {
  $priorities = array();
  $priorities[10] = array(
    'label' => __('Trivial', 'scifi-task-manager'),
    'color' => '#4FC5CF',
  );
  $priorities[25] = array(
    'label' => __('Low', 'scifi-task-manager'),
    'color' => '#45D6AF',
  );
  $priorities[50] = array(
    'label' => __('Normal', 'scifi-task-manager'),
    'color' => '#9ED645',
  );
  $priorities[75] = array(
    'label' => __('High', 'scifi-task-manager'),
    'color' => '#FF6600',
  );
  $priorities[90] = array(
    'label' => __('Critical', 'scifi-task-manager'),
    'color' => '#CC0000',
  );
  $priorities = apply_filters('scifi-task-manager-priorities', $priorities);
  if ($priority === 'all') {
    return $priorities;
  }
  else {
    return empty($priorities[$priority]) ? NULL : ($label ? $priorities[$priority]['label'] : $priorities[$priority]);
  }
}

/**
 * Get registered statuses or just a one
 *
 * @param $status
 * @param $label
 *
 * @return array|object
 */
function scifi_task_manager_get_statuses($status = 'all', $label = FALSE) {
  $statuses = array();
  $statuses['scifitm-pending'] = array(
    'label' => __('Pending', 'scifi-task-manager'),
    'progress' => 80,
    'color' => '#cccccc',
  );
  $statuses['scifitm-rejected'] = array(
    'label' => __('Rejected', 'scifi-task-manager'),
    'progress' => 70,
    'color' => '#333333',
  );
  $statuses['scifitm-hold'] = array(
    'label' => __('Hold', 'scifi-task-manager'),
    'progress' => 60,
    'color' => '#FFF7BA',
  );
  $statuses['scifitm-inprogress'] = array(
    'label' => __('In Progress', 'scifi-task-manager'),
    'progress' => 40,
    'color' => '#CAF28A',
  );
  $statuses['scifitm-waitreview'] = array(
    'label' => __('Awaiting review', 'scifi-task-manager'),
    'progress' => 40,
    'color' => '#E3B1F0',
  );
  $statuses['scifitm-inreview'] = array(
    'label' => __('In Review', 'scifi-task-manager'),
    'progress' => 30,
    'color' => '#C564DE',
  );
  $statuses['scifitm-resolved'] = array(
    'label' => __('Resolved', 'scifi-task-manager'),
    'progress' => 0,
    'color' => '#9ED645',
  );
  $statuses['scifitm-completed'] = array(
    'label' => __('Completed', 'scifi-task-manager'),
    'progress' => 0,
    'color' => '#9ED645',
  );
  
  $statuses = apply_filters('scifi-task-manager-statuses', $statuses);
  if ($status === 'all') {
    return $statuses;
  }
  else {
    return empty($statuses[$status]) ? NULL : ($label ? $statuses[$status]['label'] : $statuses[$status]);
  }
}

/**
 * Prepare task postdata
 *
 * @param $post
 * @param $postattr
 *
 * @return array|object
 */
function _scifi_task_manager_prepare_post_data($post, $postattr = array()) {
  $returnobj = is_object($post);
  if (!$returnobj) {
    $post = (object) $post;
  }
  if ($post->post_type === 'scifi-task-manager') {
    // Do stuff.
    if (!$post->post_title && !empty($post->ID)) {
      $post->post_title = $post->ID;
    }
    if (!empty($postattr['post_name_taskid'])) {
      $post->post_name = '#' . preg_replace('#[^\w\d\-\_\/]+#i', '', substr(strtoupper($postattr['post_name_taskid']), 0, 31));
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
      $output = $status['label'];
    }
  }
  elseif ($column_name == 'priority' || $column_name == 'menu_order') {
    $priority = scifi_task_manager_get_priorities($post->menu_order);
    if ($priority) {
      $output = $priority['label'];
    }
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
      $output = date_i18n(get_option('date_format', 'U'), $deadline);
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
    if (!$args['post__in']) {
      return array();
    }
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

function _scifi_task_manager_cssjs() {
  if (get_current_screen()->base == 'dashboard' || get_current_screen()->post_type == 'scifi-task-manager') {
    ?>
    <style>
      .preview-active .preview-content {
        background: #fff;
        color: #555;
        height: 20px;
        border-bottom: 1px solid #fff;
      }

      #scifi-task-manager-single-task-preview {
        background: #fff;
        padding: 2%;
        border-left: 1px solid #E5E5E5;
        border-right: 1px solid #E5E5E5;
        clear: both;
        width: 95.8%;
        float: left;
        margin-bottom: 0;
      }

      #scifi_task_manager_widget p.info {
        color: #AAA;
        font-size: 1.2em;
        font-weight: bold;
        text-align: center;
        padding-bottom: 1em;
      }

      .dashboard-widget-control-form fieldset {
        display: inline-block;
        vertical-align: top;
        margin: 11px;
      }

      .dashboard-widget-control-form,
      #scifi-task-manager-publish.postbox .inside #minor-publishing {
        margin: 10px;
      }

      body.post-type-scifi-task-manager .actions.bulkactions,
      #scifi-task-manager-attachments.postbox .inside,
      #scifi-task-manager-publish.postbox .inside,
      #scifi-task-manager-subtasks.postbox .inside,
      #dashboard-widgets #scifi_task_manager_widget .inside {
        margin: 0;
        padding: 0;
      }

      body.post-type-scifi-task-manager .actions.bulkactions {
        width: 80%;
        clear: both;
        float: left;
      }

      body.post-type-scifi-task-manager .tablenav.top .bulkactions,
      body.post-type-scifi-task-manager .inline-edit-col-right,
      body.post-php.post-type-scifi-task-manager #wpbody-content h2 {
        display: none;
      }

      body.post-php.post-type-scifi-task-manager #wpbody-content form#post {
        margin-top: 30px;
      }

      body.post-php.post-type-scifi-task-manager #wpbody-content .comments-box .column-author > strong {
        display: block;
      }
      body.post-php.post-type-scifi-task-manager #wpbody-content .comments-box .column-author > * {
        display: none;
      }
      body.post-php.post-type-scifi-task-manager #wpbody-content #commentsdiv .inside .column-author {
        width: 15%;
      }

      #scifi-task-manager-attachments.postbox .inside .wp-list-table,
      #scifi-task-manager-subtasks.postbox .inside .wp-list-table,
      #dashboard-widgets #scifi_task_manager_widget .wp-list-table {
        border: 0;
      }

      #scifi_task_manager_widget td p {
        margin: 0;
      }

      /* General colorization ans styling */
      body.post-type-scifi-task-manager .wp-list-table tbody .column-status,
      body.post-type-scifi-task-manager .wp-list-table tbody .column-menu_order,
      #scifi_task_manager_widget .wp-list-table tbody .column-status,
      #scifi_task_manager_widget .wp-list-table tbody .column-menu_order {
        text-align: center;
        vertical-align: middle;
        font-size: .8em;
        font-weight: bold;
        text-shadow: 0 0 1px #000;
        color: #fff;
      }

      body.post-type-scifi-task-manager .wp-list-table .column-status,
      body.post-type-scifi-task-manager .wp-list-table .column-menu_order,
      #scifi_task_manager_widget .wp-list-table .column-status,
      #scifi_task_manager_widget .wp-list-table .column-menu_order {
        width: 100px;
      }

      <?php
      foreach (scifi_task_manager_get_priorities('all') as $priority_number => $priority) {
        echo ".scifi-task-manager-priority-{$priority_number} .column-menu_order {background: " . $priority['color'] . ';} ';
      }
      foreach (scifi_task_manager_get_statuses('all') as $status_name => $status) {
        echo ".scifi-task-manager-status-{$status_name} .column-status {background: " . $status['color'] . ';} ';
      }
      ?>
    </style>
  <?php
  }
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
    update_option('scifi-task-manager_mailer', !empty($_POST['scifi-task-manager_mailer']));
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
              <a href="<?php echo admin_url('edit-tags.php?taxonomy=scifi-task-manager-tag&post_type=scifi-task-manager')?>">
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

        <tr>
          <th>
            <?php _e('Mail notification', 'scifi-task-manager')?>
          </th>
          <td>
            <p>
              <input type="checkbox" id="scifi-task-manager_mailer" name="scifi-task-manager_mailer" value="1" <?php checked(get_option('scifi-task-manager_mailer'), 1)?> />
              <label for="scifi-task-manager_mailer">
                <?php _e('Enable', 'scifi-task-manager')?>
              </label>
            </p>
            <p><small><?php _e('Recieve mails with changes when tasks are created or modified, or are being commented. Users have ability to unsubscribe from mail notification.', 'scifi-task-manager')?></small></p>
          </td>
        </tr>

      </table>
      
      <?php echo submit_button()?>
    </form>
  </div>
  <?php
}

/**
 * Send mail for task changes
 *
 * @param $action
 * @param $post
 * @param null $post_before
 *
 * @return bool
 */
function scifi_task_manager_send_mails($action, $post, $old_post = NULL) {

  // Check global option.
  if (!get_option('scifi-task-manager_mailer')) {
    return NULL;
  }

  // If this is called by comment notify, then $post arg is comment actually,
  // so swap them
  if ($action == 'comment') {
    $comment = $post;
    $post = get_post($comment->comment_post_ID);
  }

  $allowed_post_statuses = scifi_task_manager_get_statuses('all');

  // If problem with $post, then exit
  if (!$post || $post->post_type != 'scifi-task-manager' || empty($allowed_post_statuses[$post->post_status])) {
    return NULL;
  }

  $current_user = wp_get_current_user();
  $post_meta = get_post_meta($post->ID);

  // Gathering the recipients
  $recipients_uids = empty($post_meta['_scifi-task-manager_assignee']) ? array() : $post_meta['_scifi-task-manager_assignee'];
  $recipients_uids[] = $post->post_author;
  $recipients_uids = array_unique($recipients_uids);
  $recipients = array();
  foreach ($recipients_uids as $recipient) {
    $_recipient_userdata = get_userdata($recipient);
    if ($_recipient_userdata->_scifi_task_manager_recieve_mails === '' ? TRUE : !empty($_recipient_userdata->_scifi_task_manager_recieve_mails)) {
      $recipients[] = sprintf('%s <%s>', $_recipient_userdata->data->display_name, $_recipient_userdata->data->user_email);
    }
  }

  // Set tokens
  $message_tokens = array(
    '{reporter}'   => $current_user->data->display_name,
    '{tasklink}'   => sprintf('<a href="%s" target="_blank">%s</a>', get_post_permalink($post->ID), $post->post_name),
    '{taskid}'     => $post->post_name,
    '{deadline}'   => empty($post_meta['_scifi-task-manager_deadline'][0]) ? '--' : $post_meta['_scifi-task-manager_deadline'][0],
    '{tasktitle}'  => $post->post_title,
    '{taskbody}'   => $post->post_content,
    '{taskstatus}' => $post->post_status,
    '{site}'       => get_bloginfo('name'),
    '{changelist}' => '',
  );

  // Add action message
  if ($action == 'add') {
    $subject = sprintf(__('Created new task - %s by %s', 'scifi-task-manager'), $message_tokens['{taskid}'], $message_tokens['{reporter}']);
    $message = __('
Hello,

{reporter} just create new task changes in task {tasklink} ({tasktitle});

--
This mail is sent automatically by task management system. Please do not reply.
{site}', 'scifi-task-manager');
  }

  // Update action message
  elseif ($action == 'update') {
    $message_tokens['{changelist}'] = '';
    if ($post->post_status != $old_post->post_status) {
      $message_tokens['{changelist}'] .= "\n * " . sprintf(__('Status changed from %s to %s', 'scifi-task-manager'), scifi_task_manager_get_statuses($old_post->post_status, 'label'), scifi_task_manager_get_statuses($post->post_status, 'label'));
    }
    if ($post->menu_order != $old_post->menu_order) {
      $message_tokens['{changelist}'] .= "\n * " . sprintf(__('Priority changed from %s to %s', 'scifi-task-manager'), scifi_task_manager_get_priorities($old_post->menu_order, 'label'), scifi_task_manager_get_priorities($post->menu_order, 'label'));
    }
    if ($post->post_author != $old_post->post_author) {
      $message_tokens['{changelist}'] .= "\n * " . sprintf(__('Reporter changed from %s to %s', 'scifi-task-manager'), get_userdata($old_post->post_author, 'display_name')->display_name, get_userdata($post->post_author, 'display_name')->display_name);
    }
    if ($post->post_parent != $old_post->post_parent) {
      $message_tokens['{changelist}'] .= "\n * " . sprintf(__('Parent task changed from %s to %s', 'scifi-task-manager'), get_the_title($old_post->post_parent), get_the_title($post->post_parent));
    }

    if (!$message_tokens['{changelist}']) {
      $message_tokens['{changelist}'] = '--';
    }

    $subject = sprintf(__('Updated task - %s by %s', 'scifi-task-manager'), $message_tokens['{taskid}'], $message_tokens['{reporter}']);
    $message = __('
Hello,

{reporter} just make changes in task {tasklink} ({tasktitle})

Deadline: {deadline}

Changes: {changelist}

--
This mail is sent automatically by task management system. Please do not reply.
{site}', 'scifi-task-manager');
  }

  // Comment action message
  elseif ($action == 'comment') {
    $message_tokens['{commenter}'] = $comment->comment_author;
    $message_tokens['{comment}'] = $comment->comment_content;
    $subject = sprintf(__('New comment on task - %s by %s', 'scifi-task-manager'), $message_tokens['{taskid}'], $message_tokens['{commenter}']);
    $message = __('
Hello,

{commenter} just make comment on your task {tasklink} ({tasktitle});

<quote>
{comment}
</quote>

--
This mail is sent automatically by task management system. Please do not reply.
{site}', 'scifi-task-manager');
  }

  else {
    return NULL;
  }

  $message = wpautop(wptexturize(strtr($message, $message_tokens)));
  $headers = array(
    'Content-Type: text/html; charset=UTF-8',
  );
  return wp_mail($recipients, $subject, $message, $headers);
}