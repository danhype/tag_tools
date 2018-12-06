<?php
/**
 * Show users with a lot of content created with the given tag
 *
 * @uses $vars['tag'] the tag being shown
 */

$tag = elgg_extract('tag', $vars);
if (elgg_is_empty($tag)) {
	return;
}

$tag = strtolower($tag);

// fix for elasticsearch
set_input('sort', 'time_created');

$content = elgg_list_entities([
	'query' => $tag,
	'type' => 'user',
	'type_subtype_pairs' => get_registered_entity_types(),
	'search_type' => 'entities',
	'limit' => 10,
	'pagination' => false,
	'sort' => 'time_created',
	'order' => 'desc',
], 'elgg_search');
if (empty($content)) {
	return;
}

$more = elgg_view('output/url', [
	'text' => elgg_echo('tag_tools:tag:view:more'),
	'href' => elgg_generate_url('default:search', [
		'q' => $tag,
		'sort' => 'time_created',
	]),
	'is_trusted' => true,
]);

echo elgg_view_module('tag_content', elgg_echo('tag_tools:tag:content:content'), $content, ['menu' => $more]);