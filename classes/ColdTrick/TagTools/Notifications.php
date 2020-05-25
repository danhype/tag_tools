<?php

namespace ColdTrick\TagTools;

use Elgg\Notifications\NotificationEvent;

class Notifications {
	
	/**
	 * Prevent subscribers for the tag notifications
	 *
	 * @param \Elgg\Hook $hook 'get', 'subscriptions'
	 *
	 * @return void|array
	 */
	public static function getSubscribers(\Elgg\Hook $hook) {
		
		if (!self::validateNotificationEvent($hook->getParams())) {
			// not the correct notification event
			return;
		}
		
		/* @var $event \Elgg\Notifications\SubscriptionNotificationEvent */
		$event = $hook->getParam('event');
		if (!$event instanceof NotificationEvent) {
			return;
		}
		
		$relationship = $event->getObject();
		if (!$relationship instanceof \ElggRelationship) {
			return;
		}
		
		$entity = get_entity($relationship->guid_two);
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		$sending_tags = self::getUnsetTagsForEntity($entity);
		if (empty($sending_tags)) {
			return [];
		}
		
		
		$validate_access = function(\ElggUser $user) use ($entity) {
			static $acl_members;
			
			if ($entity->access_id === ACCESS_PRIVATE) {
				return false;
			}
			
			if (!has_access_to_entity($entity, $user)) {
				return false;
			}
			
			if (!isset($acl_members)) {
				$acl_members = false;
				
				if (get_access_collection($entity->access_id) !== false) {
					// this is an acl
					$acl_members = get_members_of_access_collection($entity->access_id, true);
				}
			}
			
			if ($acl_members === false) {
				// not an acl
				return true;
			}
			
			return in_array($user->guid, $acl_members);
		};
		
		$tag_subscribers = [];
		
		// get interested users
		$users_batch = elgg_get_entities([
			'type' => 'user',
			'annotation_name_value_pairs' => [
				'name' => 'follow_tag',
				'value' => $sending_tags,
				'case_sensitive' => false,
			],
			'limit' => false,
			'batch' => true,
		]);
		
		/* @var $user \ElggUser */
		foreach ($users_batch as $user) {
			
			// check user access
			if (!$validate_access($user)) {
				continue;
			}
			
			// get the notification settings of the user for one of the sending tags
			// this will prevent duplicate notifications,
			foreach ($sending_tags as $tag) {
				
				if (!tag_tools_is_user_following_tag($tag, $user->guid)) {
					// user is not following this tag, check the next
					continue;
				}
				
				$notifiction_settings = tag_tools_get_user_tag_notification_settings($tag, $user->guid);
				if (empty($notifiction_settings)) {
					// no notification settings for this tag
					continue;
				}
				
				if (isset($tag_subscribers[$user->guid])) {
					$tag_subscribers[$user->guid] = array_merge($tag_subscribers[$user->guid], $notifiction_settings);
					$tag_subscribers[$user->guid] = array_unique($tag_subscribers[$user->guid]);
				} else {
					$tag_subscribers[$user->guid] = $notifiction_settings;
				}
			}
		}
		
		if (!empty($tag_subscribers)) {
			return $tag_subscribers;
		}
		
		return [];
	}
	
	/**
	 * Make the tag tools notification
	 *
	 * @param \Elgg\Hook $hook 'prepare', 'notification:create:relationship:tag_tools:notification'
	 *
	 * @return void|\Elgg\Notifications\Notification
	 */
	public static function prepareMessage(\Elgg\Hook $hook) {
		
		if (!self::validateNotificationEvent($hook->getParams())) {
			return;
		}
		$return_value = $hook->getValue();
		$recipient = $return_value->getRecipient();
		$method = $hook->getParam('method');
		$relationship = $hook->getParam('object');
		$language = $hook->getParam('language');
		
		$entity = get_entity($relationship->guid_two);
		
		$sending_tags = self::getUnsetTagsForEntity($entity);
		$tag = [];
		foreach ($sending_tags as $sending_tag) {
			
			if (!tag_tools_is_user_following_tag($sending_tag, $recipient->guid)) {
				// user is not following this tag
				continue;
			}
			
			if (!tag_tools_check_user_tag_notification_method($sending_tag, $method, $recipient->guid)) {
				continue;
			}
			
			$tag[] = $sending_tag;
		}
		$tag = implode(', ', $tag);
		
		// is this a new entity of an update on an existing
		$time_diff = (int) $entity->time_updated - (int) $entity->time_created;
		if ($time_diff < 60) {
			// new entity
			$return_value->subject = elgg_echo('tag_tools:notification:follow:subject', [$tag], $language);
			$return_value->summary = elgg_echo('tag_tools:notification:follow:summary', [$tag], $language);
			$return_value->body = elgg_echo('tag_tools:notification:follow:message', [$tag, $entity->getURL()], $language);
		} else {
			// updated entity
			$return_value->subject = elgg_echo('tag_tools:notification:follow:update:subject', [$tag], $language);
			$return_value->summary = elgg_echo('tag_tools:notification:follow:update:summary', [$tag], $language);
			$return_value->body = elgg_echo('tag_tools:notification:follow:update:message', [$tag, $entity->getURL()], $language);
		}
		
		return $return_value;
	}
	
	/**
	 * Cleanup some stuff
	 *
	 * @param \Elgg\Hook $hook 'send:after', 'notifications'
	 *
	 * @return void
	 */
	public static function afterCleanup(\Elgg\Hook $hook) {
		
		if (!self::validateNotificationEvent($hook->getParams())) {
			// not the correct notification event
			return;
		}
		
		/* @var $event \Elgg\Notifications\SubscriptionNotificationEvent */
		$event = $hook->getParam('event');
		
		/* @var $relationship \ElggRelationship */
		$relationship = $event->getObject();
		
		$entity = get_entity($relationship->guid_two);
		
		// cleanup the relationship
		remove_entity_relationships($entity->guid, 'tag_tools:notification', true);
		
		// save the newly sent tags
		$sending_tags = self::getUnsetTagsForEntity($entity);
		if (empty($sending_tags)) {
			return;
		}
		
		tag_tools_add_sent_tags($entity, $sending_tags);
	}

	/**
	 * Set the correct URL for the notification relationship
	 *
	 * @param \Elgg\Hook $hook 'relationship:url', 'relationship'
	 *
	 * @return void|string
	 */
	public static function getNotificationURL(\Elgg\Hook $hook) {
		
		$relationship = elgg_extract('relationship', $hook->getParams());
		if (!$relationship instanceof \ElggRelationship) {
			return;
		}
		
		if ($relationship->relationship !== 'tag_tools:notification') {
			return;
		}
		
		$entity = get_entity($relationship->guid_two);
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		return $entity->getURL();
	}
	
	/**
	 * Validate that we have a tag_tools notification event
	 *
	 * @param array $params the hook params to check
	 *
	 * @return bool
	 */
	protected static function validateNotificationEvent($params) {
		
		if (empty($params) || !is_array($params)) {
			return false;
		}
		
		$event = elgg_extract('event', $params);
		if (!$event instanceof \Elgg\Notifications\SubscriptionNotificationEvent) {
			return false;
		}
		
		if ($event->getAction() !== 'create') {
			return false;
		}
		
		$relationship = $event->getObject();
		if (!$relationship instanceof \ElggRelationship) {
			return false;
		}
		
		if ($relationship->relationship !== 'tag_tools:notification') {
			return false;
		}
		
		return true;
	}

	/**
	 * Get the unsent tags
	 *
	 * @param \ElggEntity $entity the entity to get for
	 *
	 * @return string[]
	 */
	protected static function getUnsetTagsForEntity(\ElggEntity $entity) {
		
		$entity_tags = $entity->tags;
		
		// Cannot use empty() because it would evaluate
		// the string "0" as an empty value.
		if (is_null($entity_tags)) {
			// shouldn't happen
			return [];
		} elseif (!is_array($entity_tags)) {
			$entity_tags = [$entity_tags];
		}
		
		$sent_tags = $entity->getPrivateSetting('tag_tools:sent_tags');
		if (!empty($sent_tags)) {
			$sent_tags = json_decode($sent_tags, true);
		} else {
			$sent_tags = [];
		}
		
		return array_diff($entity_tags, $sent_tags);
	}
}
