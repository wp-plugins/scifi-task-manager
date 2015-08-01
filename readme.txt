=== scifi Task Manager ===
Contributors: dimitrov.adrian
Tags: tasks, issues, project manager, project planning, issue tracking, bug
Requires at least: 3.7
Tested up to: 4.2
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

scifi Task Manager is simple admin dash only task manager. Purpose of it is to manage and
organize the work of site that is living in it. The plugin add dashboard widget for easy overview,
and full list in Dashboard -> Tasks menu. The tasks itself are not public accessible it is
member (roles can be configured by settings) only information.


== Screenshots ==

1. Admin dashboard widget
2. Add new task
3. Tasks overview


== Frequently Asked Questions ==

= How can I add own priorities =
The plugin itself doesn't provide option to setup your own priorities via the admin panel.
I think I do most common cases, but if you really need to add/remove priorities you can do it via filter hook 'scifi-task-manager-priorities'

*Simple example*
`add_filter('scifi-task-manager-priorities', function($priorities) {
`
`
`  // Add custom priority.
`  $priorities[99] = array(
`
`    // Label of the priority
`    'label' => 'Very very critical',
`
`    // Color code
`    'color' => '#ff0000',
`  );
`
`
`  // Remove status.
`  unset($priorities[75]);
`
`  return $priorities;
`});


= How can I add own statuses =
The plugin itself doesn't provide option to setup your own priorities via the admin panel. But like the priorities, there are
hook about it 'scifi-task-manager-statuses'


*Simple example*
```
add_filter('scifi-task-manager-statuses', function($statuses) {


  // Add custom status.
  $statuses['myplugin-special-status1'] = array(

    // Label of the status
    'label' => 'My status 1',

    // Status progress (0-100)
    'progress' => 30,

    // Color code
    'color' => '#ff0000',
  );


  // Remove status.
  unset($statuses['scifitm-resolved']);

  return $statuses;
});
```

Please be carefull with statuses because some of the task could became *invisible* if you remove status that contain tasks.



= Why I see tasks but the fields are not editable =

If you notice such behaviour, this is because the task is not for you, this means that you are not owner or not assegnee for the task.


= The plugin doesn't work =

The plugin depends of PHP 5.4, so please check your PHP version first.


= What are the future plans about the plugin =

I have a little task list that I will add in next releases.

* FE task creation
* Full preview lock
* More translations
* Custom capabilities


== Installation ==

1. Visit 'Plugins > Add New'
2. Search for 'scifi Task Manager'
3. Activate scifi Task Manager from your Plugins page.
4. Go to Dashboard -> Tasks, and add new task
5. You can configure Dashboard widget by your needs.
6. Settings -> scifi Task Manager and edit settings like a roles, tags support and menu position


== Changelog ==

= 0.8.1 =
* Fixed bug with required email in settings reported by <samwilson> https://wordpress.org/support/topic/email-field-is-required?replies=1
* Fixed l10n domain for menu items
* Updated Bulgarian language

= 0.8 =
* Added new column for admin widget "Description"
* Fixed bug "scrollable assignee checkbox" https://wordpress.org/support/topic/scrollable-assignee-checkbox

= 0.7 =
* Added mail sender from configuration (note that wp_mail_from and wp_mail_from_name can override plugin's configuration)
* Disabled post editing when user have no access to task
* Added deadline in task change mail template
* Fixed bug with missing is_ajax() reported by <Muneera_Salah>
* Fixed mail sending to only reporter reported by <Muneera_Salah>
* Fixed bug in dashboard when tags support is disabled
* UI Fixes

= 0.6 =
* Added mail notify support
* Changed status and progress colors, colors are now values
* Changes in UI
* Fixed bug appending "-2" to task names
* Fixed bug with removing quick actions from all post types
* Fixed bug preventing all users in roles to be shown as assignees or reporter

= 0.5 =
* Open View instead of Edit for owner users
* Fixed user's widget, showing all tasks when no are assigned to user

= 0.4 =
* Fix menu selection
* Added status counts in task list
* Small tweaks

= 0.3 =
* Fixed permission issue reported by <jonh M reis>
* Fixed some visual editor glitches on 4.0
* Added configuration page
* Added option to select user roles that have access to task manager
* Added option to enable/disable tags support
* Added option to change place in admin panel that menu is appeared

= 0.2 =
* First public release

= 0.1 =
* Initial bump

