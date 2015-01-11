<?php if ($posts):?>
  <table class="wp-list-table widefat fixed">
    <thead>
      <?php if (!empty($config['columns']['priority'])):?>
      <th class="scifi-task-manager-admin-widget-col-priority menu_order column-menu_order">
        <?php _e('Priority', 'scifi-task-manager')?>
      </th>
      <?php endif?>
      <?php if (!empty($config['columns']['status'])):?>
      <th class="scifi-task-manager-admin-widget-col-status status column-status">
        <?php _e('Status', 'scifi-task-manager')?>
      </th>
      <?php endif?>
      <th class="scifi-task-manager-admin-widget-col-task">
        <?php _e('Task', 'scifi-task-manager')?>
      </th>
      <?php if (!empty($config['columns']['info'])):?>
      <th class="scifi-task-manager-admin-widget-col-infoinfo">
        <?php _e('Info', 'scifi-task-manager')?>
      </th>
      <?php endif?>
    </thead>
    <tbody>
    <?php foreach ($posts as $post):?>
      <tr class="<?php echo implode(' ', get_post_class('', $post->ID))?>">
        <?php if (!empty($config['columns']['priority'])):?>
        <td class="scifi-task-manager-admin-widget-col-priority menu_order column-menu_order">
          <?php echo _scifi_task_manager_format_column('priority', $post->ID, TRUE)?>
        </td>
        <?php endif?>
        <?php if (!empty($config['columns']['status'])):?>
        <td class="scifi-task-manager-admin-widget-col-status status column-status">
          <?php echo _scifi_task_manager_format_column('status', $post->ID, TRUE)?>
        </td>
        <?php endif?>
        <td class="scifi-task-manager-admin-widget-col-task">
          <p>
            <a href="<?php echo get_edit_post_link($post->ID)?>">
              <?php echo _scifi_task_manager_format_column('taskid', $post->ID, TRUE)?>
            </a>
          </p>
          <?php if ($post->post_title):?>
          <p>
            <?php echo $post->post_title?>
          </p>
          <?php endif?>
        </td>
        <?php if (!empty($config['columns']['info'])):?>
        <td class="scifi-task-manager-admin-widget-col-taskinfo">
          <?php if (($deadline = _scifi_task_manager_format_column('deadline', $post->ID, TRUE))):?>
          <p>
            <strong><?php _e('Deadline', 'scifi-task-manager')?>:</strong>
            <?php echo $deadline?>
          </p>
          <?php endif?>
          <?php if (get_option('scifi-task-manager_tags')):?>
          <p class="scifi-task-manager-admin-widget-col-tags">
            <?php echo get_the_term_list($post->ID, 'scifi-task-manager-tag', '<strong>' . __('Tags: ', 'scifi-task-manager') . '</strong>', ', ' )?>
          </p>
          <?php endif?>
          <p class="scifi-task-manager-admin-widget-col-author">
            <strong><?php _e('Reported by', 'scifi-task-manager')?>:</strong>
            <?php echo _scifi_task_manager_format_column('reporter', $post->ID, TRUE)?>
          </p>
          <p class="scifi-task-manager-admin-widget-col-assignee">
            <strong><?php _e('Assignee', 'scifi-task-manager')?>:</strong>
            <?php echo _scifi_task_manager_format_column('assignee', $post->ID, TRUE)?>
          </p>
        </td>
        <?php endif?>
      </tr>
    <?php endforeach?>
    </tbody>
  </table>
<?php else:?>
  <p class="info">
    <?php echo get_post_type_object('scifi-task-manager')->labels->not_found?>
  </p>
<?php endif?>
