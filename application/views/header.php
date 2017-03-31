<!DOCTYPE html>
<html lang="hu">
	<head>
		<meta charset="utf-8" />

		<title>Capital Factory Game</title>

		<meta name="description" content="">
		<meta name="author" content="">

		<link rel="icon" type="image/png" href="/i/site/favicon.ico" sizes="16x16"/>
		<link href="http://fonts.googleapis.com/css?family=Roboto:100,400,500,300,700&amp;subset=latin,latin-ext" media="screen" rel="stylesheet" type="text/css" >
		<link href="http://fonts.googleapis.com/css?family=Roboto+Condensed:100,400,500,300,700&amp;subset=latin,latin-ext" media="screen" rel="stylesheet" type="text/css" >
		<link rel="stylesheet" href="<?=BASE_URL;?>static/css/style.css?v=<?=VERSION;?>" type="text/css" media="screen" />
		<meta name="viewport" content="width=device-width, initial-scale=1" >
		<? if (APP_ENV == 'production') : ?>
			<script type="text/javascript" src="<?=BASE_URL;?>static/js/libraries/jquery/jquery.min.js"></script>
		<? else : ?>
			<script type="text/javascript" src="<?=BASE_URL;?>static/js/libraries/jquery/jquery.js"></script>
		<? endif; ?>
		<script type="text/javascript" src="<?=BASE_URL;?>static/plugins/wx/core.js?v=<?=VERSION;?>"></script>
		<script type="text/javascript" src="<?=BASE_URL;?>static/js/main.js?v=<?=VERSION;?>"></script>
		<script type="text/javascript" src="<?=BASE_URL;?>static/js/datatables.js?v=<?=VERSION;?>"></script>
		<script type="text/javascript" src="<?=BASE_URL;?>static/js/sweetalert.min.js?v=<?=VERSION;?>"></script>

		<script type="text/javascript">
			$(document).ready(function(){
				BASE_HOST = '<?=BASE_URL;?>';

				var TRANSLATOR = {
					TEST : 'test'
				};
			});
		</script>
	</head>
	<body>