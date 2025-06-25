<?php

class qa_html_theme_layer extends qa_html_theme_base {

    function doctype()
    {
        if ($this->template == 'account') {
            $emailopt_form = $this->emailopt_form_generate();
            if ($emailopt_form) {
                $this->content['form_emailopt'] = $emailopt_form;
            }
        }

        parent::doctype();
    }

    function emailopt_form_generate()
    {
        if (!qa_get_logged_in_userid()) return null;

        require_once QA_INCLUDE_DIR . 'db/metas.php';
        $userid = qa_get_logged_in_userid();

        if (qa_clicked('emailoptsettings_save')) {
            // Save 1 = opt-out, 0 = allow emails
            $field_value = empty(qa_post_text('emailopt_check_box')) ? "1" : "0";
            qa_db_usermeta_set($userid, 'emailopt', $field_value);
            qa_redirect($this->request, array('ok' => qa_lang_html('emailopt/email_preference_saved')));
        }

        $optout = (bool) qa_db_usermeta_get($userid, 'emailopt');
        $ok = qa_get('ok') ? qa_get('ok') : null;

        return array(
            'ok'     => $ok,
            'style'  => 'tall',
            'title'  => qa_lang_html('emailopt/email_section_label'),
            'tags'   => 'method="POST" action=""',
            'fields' => array(
                array(
                    'label' => qa_lang_html('emailopt/email_field_label'),
                    'tags'  => 'name="emailopt_check_box"',
                    'type'  => 'checkbox',
                    'value' => !$optout,
                ),
            ),
            'buttons' => array(
                array(
                    'label' => qa_lang_html('emailopt/save_button'),
                    'tags'  => 'name="emailoptsettings_save"',
                ),
            ),
        );
    }
}
