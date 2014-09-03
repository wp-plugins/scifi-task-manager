<p></p>
<table class="wp-list-table widefat fixed media">
  <thead>
  <th colspan="2"><?php _e('Attachment', 'scifi-task-manager')?></th>
  <th><?php _e('Type', 'scifi-task-manager')?></th>
  <th><?php _e('Size', 'scifi-task-manager')?></th>
  <th><?php _e('Date', 'scifi-task-manager')?></th>
  <th><?php _e('User', 'scifi-task-manager')?></th>
  </thead>
  <tbody>
  <?php foreach ($attachments as $attachment):?>
    <tr>
      <td class="column-icon media-icon image-icon">
        <?php echo wp_get_attachment_image($attachment->ID, array(64,64), TRUE)?>
      </td>
      <td class="title column-title">
        <p class="post-title">
          <a href="<?php echo esc_attr($attachment->guid)?>" target="_blank">
            <?php echo $attachment->post_title?>
          </a>
        </p>
        <div class="row-actions">
          <span class="edit">
            <a href="<?php echo get_edit_post_link($attachment->ID)?>">
              <?php _e('Edit')?>
            </a>
          </span>
        </div>
      </td>
      <td class="type column-type">
        <?php echo $attachment->post_mime_type?>
      </td>
      <td class="size column-size">
        <?php echo size_format(filesize(get_attached_file($attachment->ID)))?>
      </td>
      <td class="date column-date">
        <?php echo get_post_time(get_option('date_format', 'U'), TRUE, $attachment)?>
      </td>
      <td class="author column-author">
        <?php echo get_userdata($attachment->post_author)->display_name?>
      </td>
    </tr>
  <?php endforeach?>
  </tbody>
</table>
