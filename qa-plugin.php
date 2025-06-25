<?php
if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

qa_register_plugin_phrases('emails-lang.php', 'emailopt');
qa_register_plugin_layer('qa-emails-opt-layer.php', 'Adding Email opt field to the user profile');
qa_register_plugin_overrides('qa-emails-overrides.php', 'To override the email triggering function');