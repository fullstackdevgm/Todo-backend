<header class="main-header">
    <div class="container-bt">
        <div class="row">
            <div class="col-md-3 visible-md visible-lg">
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
                <a href="/?sign-in" class="btn-default btn-size-sm btn-green"><?php _e('Sign In'); ?></a>
            </div>
            <div class="hidden-md hidden-lg text-center col-sm-12">
                <a href="/?todo-for-business" class="btn-default btn-size-sm btn-info"><?php _e('Try Todo for Business'); ?></a>
                <a href="/?sign-in" class="btn-default btn-size-sm btn-green m-l-20"><?php _e('Sign In'); ?></a>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</header>
<div class="main-wrapper">
    <div class="main-container-wrapper">
        <div class="main-container">
            <div class="container-bt">
                <div class="row">
                    <h1 class="slogan"><?php _e('The Best Collaborative To-do list and Task Manager Service'); ?></h1>
                    <div class="col-md-6 col-md-offset-3">

                        <div class="auth-forms-wrapper sign-up-form-wrapper clearfix">
                            <h2 class="block-title"><?php _e('Sign Up'); ?></h2>

                            <form action="#" onsubmit="signUp();return false;">
                                <div class="sign_up_form_wrap">
                                    <div class="labeled_control">
                                        <input type="text" id="first_name" onchange="validateFirstName()" placeholder="<?php _e('First Name'); ?>" autofocus/>

                                        <div class="input_status" id="first_name_status"></div>
                                    </div>
                                    <div class="labeled_control">
                                        <input type="text" id="last_name" onchange="validateLastName()" placeholder="<?php _e('Last Name'); ?>"/>

                                        <div class="input_status" id="last_name_status"></div>
                                    </div>
                                    <div class="labeled_control">
                                        <input type="email" id="email" onchange="validateEmail()" placeholder="<?php _e('Email'); ?>"/>

                                        <div class="input_status" id="email_status"></div>
                                    </div>
                                    <div class="labeled_control">
                                        <input type="password" id="password_1" onchange="validatePasswords()" placeholder="<?php _e('Password'); ?>"/>

                                        <div class="input_status" id="password_status"></div>
                                    </div>
                                    <div class="labeled_control">
                                        <input type="password" id="verifyPassword" onchange="validateConfirmPasswords()" placeholder="<?php _e('Confirm Password'); ?>"/>

                                        <div class="input_status" id="confirm_password_status"></div>
                                    </div>
									<?php
										$trialIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL);
										$trialInterval = new DateInterval($trialIntervalSetting);
										$numOfDays = $trialInterval->format('%d');
									?>

                                    <p class="more-info"><?php printf(_('Start Your %s Day Trial of<br/>Todo Cloud Premium Features'), $numOfDays); ?></p>
                                    <button class="sign-up-button"><?php _e('Create Account'); ?></button>
                                    <div>
                                        <div class="input_status" id="sign_up_status"></div>
                                        <div class="input_status" id="error_status_message"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12">
                        <span class="terms_agree_statement">
                            <?php printf(_('By clicking Sign Up, you agree to our %s and that you have read and understand our %s'),
                                '<a href="/terms" target="_blank">'._('Terms of Service').'</a>',
                                '<a href="/privacy" target="_blank">'._('Privacy Policy').'</a>'
                                ); ?>
                        </span>
                        <div class="text-center">
                            <label class="emailoptin-label">
                                <input type="checkbox" id="emailoptin" checked/>
                                <?php _e('Receive helpful information about using Todo. We promise not to spam you and you can unsubscribe at any time.'); ?>
                            </label>
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
</div>
<script src="<?php echo TP_JS_PATH_LANDING_PAGE_FUNCTIONS; ?>"></script>
<script src="/js/jquery.webui-popover.js"></script>