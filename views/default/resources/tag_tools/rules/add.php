<?php
/**
 * Create a new tag rule
 */

// build page elements
$title = elgg_echo('tag_tools:rules:add');

$body_vars = tag_tools_rules_prepare_form_vars();

$body = elgg_view_form('tag_tools/rules/edit', [], $body_vars);

// how to display content
if (elgg_is_xhr()) {
	echo elgg_view_module('inline', $title, $body);
	return;
}

// draw page
echo elgg_view_page($title, [
	'content' => $body,
]);
