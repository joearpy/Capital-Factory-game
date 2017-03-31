<?php require_once('header.php'); ?>
<div class="container welcome-lang-select">

	<div class="welcome-container">

		<img src="/static/images/factory-logo-without-text.png" alt="" class="cf-logo">
                
                <form role="form" method="post" action="<?=BASE_URL;?>hu/">
                    <div class="login">
                    <div class="login-screen">
                            <div class="app-title">
                                    <h1>Bejelentkezés a játékba</h1>
                            </div>

                            <div class="login-form">
                                    <div class="control-group">
                                        <input type="email" class="login-field" value="" placeholder="e-mail cím" id="login-name">
                                    <label class="login-field-icon fui-user" for="login-name"></label>
                                    </div>
                                <br><br>
                                    <a class="btn btn-primary btn-large btn-block" href="<?=BASE_URL;?>hu/">Belépés</a>
                            </div>
                    </div>
                    </div>
                </form>
               
	</div>
</div>
<?php require_once('footer.php'); ?>