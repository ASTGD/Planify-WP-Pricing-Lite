<?php
/**
 * FireVPS grid layout: SaaS Grid V2 (delegates to base).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$grid_options = [
	'cta_mode'       => 'bottom_only',
	'trust_location' => 'bottom',
	'spec_icons'     => 'icons',
];

include __DIR__ . '/grid-base.php';
