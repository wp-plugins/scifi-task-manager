<?php wp_dropdown_pages(array(
  'post_type' => 'scifi-task-manager',
  'post_status' => array_keys(scifi_task_manager_get_statuses()),
  'selected' => !empty($_GET['parent_id']) ? $_GET['parent_id'] : NULL,
  'name' => 'parent_id',
  'show_option_none' => __('(general task)', 'scifi-task-manager'),
  'sort_column' => 'menu_order, post_title',
  'depth' => 2,
  'echo' => 1))?>

<?php if ($tags_terms):?>
  <select name="<?php echo esc_attr($tags_taxonomy->query_var)?>">
    <option value=""><?php echo $tags_taxonomy->labels->all_items?></option>
    <?php foreach ($tags_terms as $term):?>
    <option value="<?php echo esc_attr($term->slug)?>" <?php selected(TRUE, (!empty($_GET[$tags_taxonomy->query_var]) && $_GET[$tags_taxonomy->query_var] == $term->slug))?>>
      <?php echo $term->name?> (<?php echo $term->count?>)
      <?php endforeach?>
  </select>
<?php endif?>

<select name="author">
  <option value=""><?php _e('All reporters', 'scifi-task-manager')?></option>
  <?php foreach ($users as $user):?>
    <option value="<?php echo esc_attr($user->ID)?>" <?php selected(TRUE, (!empty($_GET['author']) && $_GET['author'] == $user->ID))?>>
      <?php echo $user->display_name?>
    </option>
  <?php endforeach?>
</select>

<select name="assignee">
  <option value=""><?php _e('All assignees', 'scifi-task-manager')?></option>
  <?php foreach ($users as $user):?>
    <option value="<?php echo esc_attr($user->ID)?>" <?php selected(TRUE, (!empty($_GET['assignee']) && $_GET['assignee'] == $user->ID))?>>
      <?php echo $user->display_name?>
    </option>
  <?php endforeach?>
</select>
