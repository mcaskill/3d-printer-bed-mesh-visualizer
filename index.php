<?php

$count = 1;

if (!empty($_REQUEST)) {
	foreach ($_REQUEST as $param => $value) {
		if (is_numeric($param)) {
			$count = min(max(abs((int) $param), 1), 100);
		}
	}
}

if (!empty($_POST['append']) && is_numeric($_POST['append'])) {
	$count += abs((int) $_POST['append']);
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
	$dataset = [ null ];
}

$count = min(max(count($dataset), $count), 100);

$template_panel = <<<'HTML'
	<v-graph-panel id="mesh-{$index}-panel">
		<fieldset id="mesh-{$index}-container">
			<legend id="mesh-{$index}-heading">Mesh Data #{$iteration}</legend>
			<div class="o-layout">
				<div class="c-mesh-input">
					<textarea id="mesh-{$index}-input" name="data[]" cols="100" rows="10" maxlength="1800" autocomplete="off" wrap="off" aria-labelledby="mesh-{$index}-heading">{$data}</textarea>
				</div>
				<div class="c-mesh-footer">
					<button id="mesh-{$index}-submit" type="button" data-control-target="panel" data-control-action="submit" aria-controls="mesh-{$index}-panel" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Visualize</button>
					<button id="mesh-{$index}-reset" type="button" data-control-target="panel" data-control-action="reset" aria-controls="mesh-{$index}-panel" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Clear</button>
				</div>
				<div class="c-mesh-output">
					<output id="mesh-{$index}-graph" class="c-graph" aria-labelledby="mesh-panel-graph"></output>
				</div>
				<div class="c-mesh-footer">
					<output id="mesh-{$index}-std" class="c-stats"></output>
				</div>
			</div>
		</fieldset>
	</v-graph-panel>
	HTML;

$page_title = '3D Printer Auto Bed Leveling Mesh Visualizer';

$favicon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 0l-11 6v12.131l11 5.869 11-5.869v-12.066l-11-6.065zm7.91 6.646l-7.905 4.218-7.872-4.294 7.862-4.289 7.915 4.365zm-16.91 1.584l8 4.363v8.607l-8-4.268v-8.702zm10 12.97v-8.6l8-4.269v8.6l-8 4.269zm6.678-5.315c.007.332-.256.605-.588.612-.332.007-.604-.256-.611-.588-.006-.331.256-.605.588-.612.331-.007.605.256.611.588zm-2.71-1.677c-.332.006-.595.28-.588.611.006.332.279.595.611.588s.594-.28.588-.612c-.007-.331-.279-.594-.611-.587zm-2.132-1.095c-.332.007-.595.281-.588.612.006.332.279.594.611.588.332-.007.594-.28.588-.612-.007-.331-.279-.594-.611-.588zm-9.902 2.183c.332.007.594.281.588.612-.007.332-.279.595-.611.588-.332-.006-.595-.28-.588-.612.005-.331.279-.594.611-.588zm1.487-.5c-.006.332.256.605.588.612s.605-.257.611-.588c.007-.332-.256-.605-.588-.611-.332-.008-.604.255-.611.587zm2.132-1.094c-.006.332.256.605.588.612.332.006.605-.256.611-.588.007-.332-.256-.605-.588-.612-.332-.007-.604.256-.611.588zm3.447-5.749c-.331 0-.6.269-.6.6s.269.6.6.6.6-.269.6-.6-.269-.6-.6-.6zm0-2.225c-.331 0-.6.269-.6.6s.269.6.6.6.6-.269.6-.6-.269-.6-.6-.6zm0-2.031c-.331 0-.6.269-.6.6s.269.6.6.6.6-.269.6-.6-.269-.6-.6-.6z"/></svg>';

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $page_title; ?></title>
	<link rel="stylesheet" href="app.css" integrity="sha384-gCjGGy41eopq8agSnw4n78+roxCMUf3XzffHaQBwMC0/Eo2kgxgMcmDWI1xe1Rqg" />
	<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?php echo htmlentities($favicon, ENT_QUOTES|ENT_HTML5); ?>" />
</head>
<body>
	<h1><?php echo $page_title; ?></h1>
	<p>Paste the Marlin G29 results to visualize the data.</p>

	<details>
		<summary id="mesh-example-heading">Example Mesh Data</summary>
		<v-graph-panel>
			<div class="o-layout">
				<div class="c-mesh-input">
					<textarea id="mesh-example-input" cols="100" rows="10" maxlength="1800" autocomplete="off" wrap="off" aria-labelledby="mesh-example-heading" readonly><?php
						echo <<<'G29'
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
					?></textarea>
				</div>
				<div class="c-mesh-footer"></div>
				<div class="c-mesh-output">
					<output id="mesh-example-graph" class="c-graph" aria-labelledby="mesh-panel-graph"></output>
				</div>
				<div class="c-mesh-footer">
					<output id="mesh-example-std" class="c-stats"></output>
				</div>
			</div>
		</v-graph-panel>
	</details>

	<v-graph-panels>
		<form class="c-visualizer" method="POST">
			<input id="mesh-panel-count" type="hidden" name="<?php echo $count; ?>" />
			<div id="mesh-panel-container">
				<?php
					$html = str_replace('{$data}', '', $template_panel);

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
			<div class="o-flex">
				<p>
					<button id="mesh-collection-submit" type="button" data-control-target="panels" data-control-action="submit" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Visualize All</button>
					<button id="mesh-collection-reset" type="reset" data-control-target="panels" data-control-action="reset" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Clear All</button>
				</p>
				<p>
					<button id="mesh-collection-append" type="submit" name="append" value="1" data-control-target="panels" data-control-action="append" aria-disabled="true" aria-describedby="mesh-control-disabled-reason">Add Panel</button>
				</p>
			</div>
			<p id="mesh-control-disabled-reason" hidden>Control unavailable</p>
			<p id="mesh-panel-graph" hidden>Interactive graph of a three-dimensional mesh</p>
			<template id="mesh-panel-template"><?php echo $template_panel; ?></template>
		</form>
	</v-graph-panels>
	<script src="https://cdn.plot.ly/plotly-strict-2.35.2.min.js" integrity="sha384-Hfut3IowdVCNAh4POKIRl2hZYJUv5ib+W9dJOxfvf9XC4IsZAw4JHl4lyf9rcIGv" crossorigin="anonymous"></script>
	<script type="module" src="app.js" integrity="sha384-lQPGPhLISIkAxXPdnqc2VVbYCFBdazMxaXlSDHNBXmbZ7yK8lhbyYBfcD7Kn76kD"></script>
</body>
</html>
