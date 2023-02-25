<?php

$count = 1;

if (!empty($_REQUEST)) {
	foreach ($_REQUEST as $param => $value) {
		if (is_numeric($param)) {
			$count = min(max(abs((int) $param), 1), 100);
		}
	}
}

if (!empty($_POST['add']) && is_numeric($_POST['add'])) {
	$count += abs((int) $_POST['add']);
}

if (!empty($_POST['data'])) {
	if (!is_array($_POST['data'])) {
		$_POST['data'] = (array) $_POST['data'];
	}

	if (!array_is_list($_POST['data'])) {
		$_POST['data'] = array_values($_POST['data']);
	}

	$dataset = array_map(fn($data) => htmlspecialchars((string) $data), $_POST['data']);
} else {
	$dataset = [];

	if ($count === 1) {
		$dataset[] = <<<'G29'
			 0        1        2        3        4        5        6        7        8        9       10       11       12       13
			 0 +0.03500 -0.02855 -0.09156 -0.15566 -0.22250 -0.29867 -0.38125 -0.45758 -0.51500 -0.54590 -0.55844 -0.56363 -0.57250
			 1 +0.01869 -0.01856 -0.05355 -0.09305 -0.14383 -0.21602 -0.30324 -0.38746 -0.45064 -0.48415 -0.49888 -0.50610 -0.51707
			 2 +0.00234 -0.00695 -0.01215 -0.02553 -0.05938 -0.12765 -0.22023 -0.31329 -0.38297 -0.41951 -0.43676 -0.44629 -0.45969
			 3 -0.01393 +0.00144 +0.02248 +0.03217 +0.01352 -0.05072 -0.14723 -0.24723 -0.32193 -0.36066 -0.37977 -0.39103 -0.40621
			 4 -0.03000 +0.00178 +0.04016 +0.06533 +0.05750 -0.00240 -0.09922 -0.20143 -0.27750 -0.31633 -0.33563 -0.34711 -0.36250
			 5 -0.04428 -0.00747 +0.03596 +0.06615 +0.06324 +0.00837 -0.08337 -0.18082 -0.25285 -0.28864 -0.30564 -0.31512 -0.32836
			 6 -0.05734 -0.02364 +0.01604 +0.04376 +0.04156 -0.00781 -0.09062 -0.17835 -0.24250 -0.27303 -0.28596 -0.29184 -0.30125
			 7 -0.07236 -0.04528 -0.01310 +0.00888 +0.00535 -0.03877 -0.11166 -0.18823 -0.24340 -0.26810 -0.27650 -0.27838 -0.28352
			 8 -0.09250 -0.07094 -0.04500 -0.02781 -0.03250 -0.07238 -0.13719 -0.20465 -0.25250 -0.27246 -0.27719 -0.27582 -0.27750
			 9 -0.11980 -0.10205 -0.08041 -0.06654 -0.07207 -0.10913 -0.16849 -0.22982 -0.27275 -0.28955 -0.29183 -0.28831 -0.28770
			10 -0.15219 -0.13878 -0.12196 -0.11197 -0.11906 -0.15424 -0.20929 -0.26559 -0.30453 -0.31884 -0.31920 -0.31399 -0.31156
			11 -0.18660 -0.17777 -0.16597 -0.16010 -0.16902 -0.20270 -0.25387 -0.30559 -0.34092 -0.35300 -0.35165 -0.34493 -0.34090
			12 -0.22000 -0.21563 -0.20875 -0.20688 -0.21750 -0.24949 -0.29656 -0.34348 -0.37500 -0.38473 -0.38156 -0.37324 -0.36750
			G29;
	} else {
		$dataset[] = '';
	}
}

$count = min(max(count($dataset), $count), 100);

$template_mesh = <<<'HTML'
	<c-panel role="group" aria-busy="false">
		<fieldset id="mesh-{$index}-container">
			<legend id="mesh-{$index}-heading">Mesh Data #{$iteration}</legend>
			<div class="o-layout">
				<div class="c-mesh-input">
					<textarea id="mesh-{$index}-input" name="data[]" cols="100" rows="10" maxlength="1800" autocomplete="off" wrap="off" aria-labelledby="mesh-{$index}-heading">{$data}</textarea>
				</div>
				<div class="c-mesh-footer">
					<button id="mesh-{$index}-submit" type="button" data-control="visualize" aria-controls="mesh-{$index}-container" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Visualize</button>
					<button id="mesh-{$index}-clear" type="button" data-control="reset" aria-controls="mesh-{$index}-container" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Clear</button>
				</div>
				<div class="c-mesh-output">
					<output id="mesh-{$index}-graph" class="c-graph"></output>
				</div>
				<div class="c-mesh-footer">
					<output id="mesh-{$index}-std" class="c-stats"></output>
				</div>
			</div>
		</fieldset>
	</c-panel>
	HTML;

$template_form_actions = <<<'HTML'
	<p>
		<button type="button" data-control="visualize" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Visualize All</button>
		<button type="reset" data-control="reset" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Clear All</button>
	</p>
	<p>
		<button type="submit" name="add" value="1" data-control="add" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Add Panel</button>
	</p>
	HTML;

$page_title = '3D Printer Auto Bed Leveling Mesh Visualizer';

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $page_title; ?></title>
	<link rel="stylesheet" href="app.css" />
</head>
<body>
	<h1><?php echo $page_title; ?></h1>
	<p>Paste the Marlin G29 results to visualize the data.</p>
	<form class="c-visualizer" method="POST">
		<input id="mesh-panel-count" type="hidden" name="<?php echo $count; ?>" />
		<div id="mesh-panel-container">
			<?php
				$html = str_replace('{$data}', '', $template_mesh);

				for ($i = 0; $i < $count; $i++) {
					echo str_replace(
						[
							'{$index}',
							'{$iteration}',
							'{$data}',
						],
						[
							$i,
							($i + 1),
							($dataset[$i] ?? '')
						],
						$html
					);
				}
			?>
		</div>
		<c-actions class="o-flex">
			<?php
				echo $template_form_actions;
			?>
		</c-actions>
		<p id="mesh-control-disabled-reason" class="screen-reader-text">Control unavailable</p>
		<template id="mesh-panel-template"><?php echo $template_mesh; ?></template>
	</form>
	<script type="module" src="app.js"></script>
</body>
</html>