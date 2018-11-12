<?php

namespace ColdTrick\TagTools;

class MenuItems {
	
	/**
	 * Add a menu item to the page menu
	 *
	 * @param string          $hook         the name of the hook
	 * @param string          $type         the type of the hook
	 * @param \ElggMenuItem[] $return_value current return value
	 * @param mixed           $params       supplied params
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function registerSettingsMenuItem($hook, $type, $return_value, $params) {
		
		if (!elgg_is_logged_in() || !elgg_in_context('settings')) {
			return;
		}
		
		$user = elgg_get_page_owner_entity();
		if (!$user instanceof \ElggUser) {
			$user = elgg_get_logged_in_user_entity();
		}
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'tag_notifications',
			'text' => elgg_echo('tag_tools:notifications:menu'),
			'href' => elgg_generate_url('settings:notification:tags', [
				'username' => $user->username,
			]),
			'section' => 'notifications',
		]);
		
		return $return_value;
	}
	
	/**
	 * Add a menu item to the filter menu
	 *
	 * @param string          $hook         the name of the hook
	 * @param string          $type         the type of the hook
	 * @param \ElggMenuItem[] $return_value current return value
	 * @param mixed           $params       supplied params
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function registerActivityTab($hook, $type, $return_value, $params) {
		
		if (!elgg_is_logged_in() || !elgg_in_context('activity')) {
			return;
		}
		
		$tags = tag_tools_get_user_following_tags();
		if (empty($tags)) {
			return;
		}
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'tags',
			'text' => elgg_echo('tags'),
			'href' => elgg_generate_url('collection:activity:tags'),
			'selected' => elgg_extract('selected', $params) === 'tags',
			'priority' => 9999,
		]);
		
		return $return_value;
	}
	
	/**
	 * Add a menu item to the follow_tag
	 *
	 * @param string          $hook         the name of the hook
	 * @param string          $type         the type of the hook
	 * @param \ElggMenuItem[] $return_value current return value
	 * @param mixed           $params       supplied params
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function registerFollowTag($hook, $type, $return_value, $params) {
		
		if (!elgg_is_logged_in()) {
			return;
		}
		
		$tag = elgg_extract('tag', $params);
		if (elgg_is_empty($tag)) {
			return;
		}
		$encoded_tag = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8', false);
		
		$following = tag_tools_is_user_following_tag($tag);
		$action_url = elgg_generate_action_url('tag_tools/follow_tag', [
			'tag' => $encoded_tag,
		]);
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'follow_tag_on',
			'icon' => 'refresh',
			'text' => elgg_echo('tag_tools:follow_tag:menu:on:text'),
			'title' => elgg_echo('tag_tools:follow_tag:menu:on'),
			'href' => $action_url,
			'item_class' => $following ? 'hidden' : '',
			'data-toggle' => 'follow-tag-off',
		]);
		
		$return_value[] = \ElggMenuItem::factory([
			'name' => 'follow_tag_off',
			'icon' => 'refresh', // @todo make icon highlighted
			'text' => elgg_echo('tag_tools:follow_tag:menu:off:text'),
			'title' => elgg_echo('tag_tools:follow_tag:menu:off'),
			'href' => $action_url,
			'item_class' => $following ? '' : 'hidden',
			'data-toggle' => 'follow-tag-on',
		]);
		
		return $return_value;
	}
	
	/**
	 * Adds admin menu items
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:admin'
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function registerAdminItems(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in() || !elgg_in_context('admin')) {
			return;
		}
		
		$result = $hook->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'tags',
			'text' => elgg_echo('admin:tags'),
			'section' => 'configure',
		]);
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'tags:search',
			'href' => 'admin/tags/search',
			'text' => elgg_echo('admin:tags:search'),
			'parent_name' => 'tags',
			'section' => 'configure',
		]);
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'tags:suggest',
			'href' => 'admin/tags/suggest',
			'text' => elgg_echo('admin:tags:suggest'),
			'parent_name' => 'tags',
			'section' => 'configure',
		]);
		$result[] = \ElggMenuItem::factory([
			'name' => 'tags:rules',
			'href' => 'admin/tags/rules',
			'text' => elgg_echo('admin:tags:rules'),
			'parent_name' => 'tags',
			'section' => 'configure',
		]);
		
		return $result;
	}
}
