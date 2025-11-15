<?php
/**
 * Overriding core mail sender: skip if user opted out.
 */
function qa_send_notification($userid, $email, $handle,$subject, $body, $subs, $html = false)
{	
	if (!$userid)
        return qa_send_notification_base($userid, $email, $handle, $subject, $body, $subs, $html);

	/* Send email if:
	 - email subject is not managed by admin, OR
	 - email subject is marked as forced
	*/

	// Load all events
    $events = qa_db_read_all_assoc(
        qa_db_query_sub('SELECT subject_key, forced FROM ^email_events')
    );

    // Build "email subject" => forced value
    $reverse = [];
    foreach ($events as $ev) {
        if (!empty($ev['subject_key'])) {
            $template = qa_lang($ev['subject_key']);
            $reverse[$template] = (int)$ev['forced'];
        }
    }

    if (!isset($reverse[$subject]) || $reverse[$subject] == 1){
        return qa_send_notification_base(
            $userid, $email, $handle, $subject, $body, $subs, $html
        );
    }
	
	// This email subject is defined but not marked as forced. We need to check the user settings.

    require_once QA_INCLUDE_DIR . 'db/metas.php';
    $prefs_csv = qa_db_usermeta_get($userid, 'emailprefs');
	
	$prefs_csv = qa_db_usermeta_get($userid, 'emailprefs');

	// No row exists - New user/ Preference not saved
	if ($prefs_csv === null) {
		return qa_send_notification_base($userid, $email, $handle, $subject, $body, $subs, $html);	
	}

    // If nothing in the preferences, skip sending
    if ($prefs_csv === '') {
        return true;
    }

    // There are some preferences. Convert CSV → subject_key array
    $subject_keys = array_filter(explode(',', $prefs_csv));

    // Convert subject_key → actual template subjects
    $allowed_subjects = [];
    foreach ($subject_keys as $key) {
        $allowed_subjects[] = qa_lang($key);
    }

    // Exact match check, if found send email
    if (in_array($subject, $allowed_subjects, true)){
        return qa_send_notification_base($userid, $email, $handle, $subject, $body, $subs, $html);
	}
	
    // Otherwise skip sending
    return true;
}