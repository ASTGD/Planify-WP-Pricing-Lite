<?php
/**
 * FireVPS grid layout: App Pricing – Soft Cards (delegates to base).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$grid_options = [
	'cta_mode'       => 'bottom_only',
	'trust_location' => 'bottom',
	'spec_icons'     => 'bullets',
];

include __DIR__ . '/grid-base.php';
