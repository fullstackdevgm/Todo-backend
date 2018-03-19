<header class="main-header">
    <div class="container-bt">
        <div class="row">
            <div class="col-md-3  visible-md visible-lg">
                <a href="/?todo-for-business" class="btn-default btn-size-sm btn-info"><?php _e('Try Todo for Business'); ?></a>
            </div>
            <div class="col-md-6">
                <div class="logo-wrapper">
                    <a href="/" class="main-home-link">
                        <img src="/images/todo-cloud-landing-page-logo.png" alt="Todo Cloud" class="small-s" />
                        <img src="/images/todo-cloud-landing-page-logo@2x.png" alt="Todo Cloud" class="retina-s"/>
                    </a>
                </div>
            </div>
            <div class="col-md-3 text-right visible-md visible-lg">
                <a href="/" class="btn-default btn-size-sm btn-green"><?php _e('Sign Up'); ?></a>
            </div>
            <div class="hidden-md hidden-lg text-center col-sm-12">
                <a href="/?todo-for-business" class="btn-default btn-size-sm btn-info"><?php _e('Try Todo for Business'); ?></a>
                <a href="/" class="btn-default btn-size-sm btn-green m-l-20"><?php _e('Sign Up'); ?></a>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</header>
<div class="main-wrapper">
<div class="main-container-wrapper">
    <div class="main-container container-bt clearfix">
        <div class="container-bt">
            <div class="row">
                <div class="col-md-6 col-md-offset-3 row">
                    <div class="auth-forms-wrapper clearfix">
                        <h2 class="block-title"><?php _e('Log In'); ?></h2>
                        <form action="#" onsubmit="signIn();return false;">
                            <input id="username" name="username" type="text" placeholder="<?php _e('Your Email'); ?>" autofocus/>
                            <input id="password" name="password" type="password" placeholder="<?php _e('Password'); ?>"/>

                            <div class="input_status" id="sign_in_error_message"></div>

                            <div class="rememberme-wrapper">
                                <label class="rememberme-label">
                                    <input type="checkbox" id="rememberme"/>
                                    <?php _e('Remember me'); ?>
                                </label>
                            </div>
                            <button id="sign_in_progress" onclick="signIn()"><?php _e('Log In'); ?></button>
                            <div class="forgot-password-wrapper">
                                <a href="#" class="forgot-password"><?php _e('Forgot Password?'); ?></a>
                            </div>
                        </form>
                    </div>
                    <div class="change-language additional-info">
                        <select>
                            <?php
                            $language_labels = TDOInternalization::getLanguageLabels();
                            $language = DEFAULT_LOCALE;
                            if ($_COOKIE['interface_language']) {
                                $language = $_COOKIE['interface_language'];
                            }
                            foreach(TDOInternalization::getAvailableLocales() as $k=>$v) : ?>
                                <option value="<?php echo $v; ?>" <?php echo ($language == $v) ? 'selected' : ''; ?>><?php echo $language_labels[$k]; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="reset-form-content hidden">
    <p><?php _e('Enter your Todo Cloud username below and we’ll send you a link to reset your password.'); ?></p>

    <form action="#" class="reset-password-form">
        <input type="text" placeholder="<?php _e('Username'); ?>" name="email" autofocus/>
        <div class="input_status username-check"></div>
        <button class="forgot-password-button disabled"><?php _e('SEND LINK'); ?></button>
    </form>
</div>
<script src="<?php echo TP_JS_PATH_LANDING_PAGE_FUNCTIONS; ?>"></script>
<script src="/js/jquery.webui-popover.js"></script>