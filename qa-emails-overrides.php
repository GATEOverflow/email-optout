<?php
/**
 * Overriding core mail sender: skip if user opted out.
 */
function qa_send_notification($userid, $email, $handle,$subject, $body, $subs, $html = false)
{
    if ($userid) {
        require_once QA_INCLUDE_DIR . 'db/metas.php';

        // returns '1' or '0' (string) or null
        $optout = qa_db_usermeta_get($userid, 'emailopt');

        if ($optout === '1') {
			error_log("optedout");
            return;                 // user opted out → abort send
        }
        // if key absent or '0' we continue
    }
	
    // Fall back to the original implementation as user opted for emails.
    //require_once QA_INCLUDE_DIR . 'app/emails.php';
    return qa_send_notification_base($userid, $email, $handle,$subject, $body, $subs, $html);
}