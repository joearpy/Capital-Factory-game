<?php require_once('header.php'); ?>

<div class="wrap container">

	<?php require_once('includes/site-header.php'); ?>

	<div id="content">
		<div class="placehold-left">
			<div id="left-container" class="content-wrapper">

				<div class="content-inner">
					<div id="welcome"></div>
					<hr class="played-line">
					<button id="start-game">Játszom!</button>
				</div>
			</div>
		</div>

		<div id="right-container" class="graph-wrapper">
			<div id="metrics-right-container">
				<div class="line"></div>
				<div class="clearfix"></div>
			</div>
		</div>

		<div class="clearfix"></div>

	</div>

	<script>
		$(document).ready(function() {
			var app = new cfgame.app('<?=$hash;?>', <?=$initData;?>);
			app.run();
		});
	</script>

	<?php require_once(VIEW_PATH . 'chart-skeleton.php'); ?>
	<?php require_once(VIEW_PATH . 'question-skeleton.php'); ?>
	<?php require_once(VIEW_PATH . 'answer-skeleton.php'); ?>
	<?php require_once(VIEW_PATH . 'result-text-skeleton.php'); ?>
	<?php require_once(VIEW_PATH . 'result-form-skeleton.php'); ?>
	<?php require_once(VIEW_PATH . 'result-circles-skeleton.php'); ?>
	<?php require_once(VIEW_PATH . 'result-circle-skeleton.php'); ?>
	<?php require_once(VIEW_PATH . 'result-hall-of-fame.php'); ?>
	<?php require_once(VIEW_PATH . 'play-again-button.php'); ?>

</div>
<?php require_once('footer.php'); ?>