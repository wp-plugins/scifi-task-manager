<div class="submitbox" id="submitpost">
  <div id="minor-publishing">

    <?php if ($action == 'edit'):?>
      <p>
        <?php printf(__('Last modification: %s', 'scifi-task-manager'), get_post_modified_time(get_option('date_format', 'U') . ' (H:i)'))?>
      </p>
    <?php endif?>

    <p>
      <?php _e('Task ID', 'scifi-task-manager')?>
      <?php if ($action == 'edit' && !empty($post->post_name)):?>
        <input id="post_name_taskid" disabled="disabled" readonly="readonly" type="text" name="post_name_taskid" value="<?php echo esc_attr($post->post_name)?>" size="14" />
      <?php else:?>
        <input type="text" id="post_name_taskid" name="post_name_taskid" value="<?php echo esc_attr($post->ID)?>" maxlength="20" size="14" />
        <script>
          (function($) {
            $(document).ready(function() {
              $('#post_name_taskid').on('keyup', function(event) {
                var newVal = $(this).val().toUpperCase().replace(/[^\w\d\-\_\/]+/, '').replace(/([\_\-\/])(?=\1)/g, "").substr(0, 31);
                $(this).val(newVal);
              });
            });
          }(jQuery));
        </script>
      <?php endif?>
    </p>

    <?php if ('' !== ($hierarchical_tasks = wp_dropdown_pages($hierarchical_tasks_qargs))):?>
      <p>
        <?php _e('Attach to', 'scifi-task-manager')?>
        <?php echo $hierarchical_tasks?>
      </p>
    <?php endif?>

    <hr />

    <p>
      <?php _e('Deadline', 'scifi-task-manager')?>
      <input type="date" name="_scifi-task-manager_deadline" value="<?php echo esc_attr($deadline)?>" />
    </p>

    <p>
      <?php _e('Priority', 'scifi-task-manager')?>
      <select name="menu_order">
        <?php foreach (scifi_task_manager_get_priorities('all') as $priority_number => $priority):?>
          <option value="<?php echo esc_attr($priority_number)?>" <?php selected($priority_number, $post->menu_order)?>>
            <?php echo $priority['label']?>
          </option>
        <?php endforeach?>
      </select>
    </p>

    <?php if ($action == 'edit'):?>
    <p>
      <?php _e('Status', 'scifi-task-manager')?>
      <select name="post_status">
        <?php foreach ($statuses as $status):?>
          <option value="<?php echo esc_attr($status->name)?>" <?php selected($status->name, $post->post_status)?>>
            <?php echo $status->label?>
          </option>
        <?php endforeach?>
      </select>
    </p>
    <?php else:?>
      <input type="hidden" name="post_status" value="scifitm-pending" />
    <?php endif?>

    <hr />
    <p>
      <?php _e('Reporter', 'scifi-task-manager')?>
      <select name="post_author">
        <?php foreach ($users as $user):?>
          <option value="<?php echo esc_attr($user->ID)?>" <?php selected(($post->post_author ? $post->post_author : get_current_user_ID()), $user->ID)?>>
            <?php if ($user->ID == get_current_user_ID()) _e('(me)', 'scifi-task-manager')?>
            <?php echo $user->display_name?>
          </option>
        <?php endforeach?>
      </select>
    </p>

    <div>
      <?php _e('Assignee', 'scifi-task-manager')?>
      <div style="max-height:15em;overflow:auto;">
        <?php foreach ($users as $user):?>
          <p>
            <input id="_scifi-task-manager_assignee-<?php echo $user->ID?>" type="checkbox" name="_scifi-task-manager_assignee[]" value="<?php echo esc_attr($user->ID)?>" <?php checked(TRUE, in_array($user->ID, $assignee))?>>
            <label for="_scifi-task-manager_assignee-<?php echo $user->ID?>">
              <?php if ($user->ID == get_current_user_ID()) _e('(me)', 'scifi-task-manager')?>
              <?php echo $user->display_name?>
            </label>
          </p>
        <?php endforeach?>
      </div>
    </div>

    <input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr(('auto-draft' == $post->post_status) ? 'draft' : $post->post_status)?>" />
    <input type="hidden" name="visibility" value="public" />
    <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr($post->post_status)?>" />

  </div>
  <div id="major-publishing-actions">
    <p>
      <?php if ($post && $action == 'edit'):?>
        <a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID)?>">
          <?php _e('Move to Trash')?>
        </a>
      <?php endif?>
      <?php submit_button(($action == 'edit' ? $labels->update_item : $labels->add_new), 'primary', 'save', FALSE)?>
    </p>
  </div>
</div>
