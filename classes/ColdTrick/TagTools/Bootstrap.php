<?php

namespace ColdTrick\TagTools;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {
	
	/**
	 * {@inheritDoc}
	 */
	public function init() {
		
		// register js/ss lib
		elgg_define_js('jquery.tag-it', ['src' => 'mod/tag_tools/vendors/jquery/tag_it/js/tag-it.min.js']);
		elgg_extend_view('css/elgg', 'css/tag_tools/jquery.tagit.css');
		elgg_extend_view('css/elgg', 'css/tag_tools/follow.css');
		elgg_extend_view('css/admin', 'css/tag_tools/admin.css');
		
		elgg_extend_view('js/elgg', 'js/tag_tools/follow.js');
	
		// page handlers
		elgg_register_page_handler('tag_tools', '\ColdTrick\TagTools\Router::tagTools');
		
		// ajax views
		elgg_register_ajax_view('tag_tools/tag/view');
		
		// menu items
		elgg_register_admin_menu_item('administer', 'search', 'tags');
		elgg_register_admin_menu_item('administer', 'suggest', 'tags');
		elgg_register_admin_menu_item('administer', 'rules', 'tags');
		
		// extend views
		elgg_extend_view('input/tags', 'tag_tools/extend_tags');
		elgg_extend_view('output/tag', 'tag_tools/output/tag');
		
		// register events
		elgg_register_event_handler('create', 'metadata', '\ColdTrick\TagTools\Rules::applyRules', 1);
		elgg_register_event_handler('create', 'metadata', '\ColdTrick\TagTools\Enqueue::createMetadata');
		elgg_register_event_handler('update:after', 'all', '\ColdTrick\TagTools\Enqueue::afterEntityUpdate');
		elgg_register_event_handler('upgrade', 'system', '\ColdTrick\TagTools\Upgrade::markOldTagsAsSent');
		elgg_register_event_handler('upgrade', 'system', '\ColdTrick\TagTools\Upgrade::checkClassHandlers');
		
		// plugin hooks
		elgg_register_plugin_hook_handler('route', 'tags', '\ColdTrick\TagTools\Router::tags');
		elgg_register_plugin_hook_handler('route', 'activity', '\ColdTrick\TagTools\Router::activity');
		elgg_register_plugin_hook_handler('route', 'notifications', '\ColdTrick\TagTools\Router::notifications');
		elgg_register_plugin_hook_handler('register', 'menu:filter', '\ColdTrick\TagTools\MenuItems::registerActivityTab');
		elgg_register_plugin_hook_handler('register', 'menu:page', '\ColdTrick\TagTools\MenuItems::registerSettingsMenuItem');
		elgg_register_plugin_hook_handler('register', 'menu:follow_tag', '\ColdTrick\TagTools\MenuItems::registerFollowTag');
		
		// notifications
		elgg_register_notification_event('relationship', 'tag_tools:notification');
		elgg_register_plugin_hook_handler('get', 'subscriptions', '\ColdTrick\TagTools\Notifications::getSubscribers', 9999);
		elgg_register_plugin_hook_handler('prepare', 'notification:create:relationship:tag_tools:notification', '\ColdTrick\TagTools\Notifications::prepareMessage');
		elgg_register_plugin_hook_handler('send:after', 'notifications', '\ColdTrick\TagTools\Notifications::afterCleanup');
		elgg_register_plugin_hook_handler('relationship:url', 'relationship', '\ColdTrick\TagTools\Notifications::getNotificationURL');
		
		// widgets
		elgg_register_widget_type('follow_tags', elgg_echo('tag_tools:widgets:follow_tags:title'), elgg_echo('tag_tools:widgets:follow_tags:description'), ['profile', 'dashboard']);
		if (elgg_is_active_plugin('tagcloud')) {
			elgg_register_widget_type('tagcloud', elgg_echo('tagcloud'), elgg_echo('tag_tools:widgets:tagcloud:description'), ['profile', 'dashboard', 'index', 'groups'], false);
		}
	}
}
