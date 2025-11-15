<?php
/*
    Email Management Plugin — Admin Page + DB Table Creation (EVENT-WISE)
    Phase 1: Admin controls types of emails that user can enable/disable.
    Storage: uses DB table qa_email_events
*/

if (!defined('QA_VERSION')) { header('Location: ../../'); exit; }

class qa_email_mgmt_admin
{
    // Table creation
	public function init_queries($tableslc)
	{
		$tbl = qa_db_add_table_prefix('email_events');

		if (!in_array($tbl, $tableslc)) {

			// First query: create table
			$sql =
			"CREATE TABLE `$tbl` (
				`eventid` INT NOT NULL AUTO_INCREMENT,
				`user_title` VARCHAR(255) NOT NULL,
				`subject_key` VARCHAR(255) NOT NULL,
				`forced` TINYINT(1) DEFAULT 0,
				`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`eventid`),
				UNIQUE KEY (`subject_key`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

			// Return both create + insert queries (Q2A will run them sequentially)
			return array_merge(
				[$sql],
				$this->default_insert_queries()
			);
		}

		return null;
	}

	private function default_insert_queries()
	{
		// [ user_title (plain text), subject_key, forced ]
		$defaults = [
			[ "Someone commented on your answer",'emails/a_commented_subject',0 ],
			[ "A related question was posted to your answer",'emails/a_followed_subject',0 ],
			[ "Your answer was selected as best",'emails/a_selected_subject',0 ],
			[ "Someone replied to your comment",'emails/c_commented_subject',0 ],
			[ "Email confirmation required",'emails/confirm_subject',1 ],
			[ "Admin received user feedback",'emails/feedback_subject',1 ],
			[ "A post has been flagged",'emails/flagged_subject',0 ],
			[ "A post requires moderation",'emails/moderate_subject',0 ],
			[ "Your new password email",'emails/new_password_subject',0 ],
			[ "You received a private message",'emails/private_message_subject',0 ],
			[ "Your question was answered",'emails/q_answered_subject',0 ],
			[ "Your question has a new comment",'emails/q_commented_subject',0 ],
			[ "A new question was posted",'emails/q_posted_subject',0 ],
			[ "Edited post requires re-approval",'emails/remoderate_subject',0 ],
			[ "Password reset requested",'emails/reset_subject',1 ],
			[ "A new user registered",'emails/u_registered_subject',0 ],
			[ "Your user account was approved",'emails/u_approved_subject',1 ],
			[ "Someone posted on your wall",'emails/wall_post_subject',0 ],
			[ "Welcome message on registration",'emails/welcome_subject',1 ],
			["Your post has been approved",'pdeleted/p_approve_reason_subject',0],
			["Your post has been permanently rejected",'pdeleted/p_hide_reason_subject',0],
			["Your post has been sent for moderation",'pdeleted/p_queue_reason_subject',0]
		];

		$queries = [];
		
		// Get correct table name
		$table = qa_db_add_table_prefix('email_events');
		
		foreach ($defaults as $row) {
			list($title, $subject_key, $forced) = $row;

			 // Escape single quotes for SQL safety
			$title_esc = str_replace("'", "''", $title);

			$queries[] =
				"INSERT INTO `{$table}` (user_title, subject_key, forced)
				 VALUES ('{$title_esc}', '{$subject_key}', {$forced})";
		}

		return $queries;
	}

    /* -------------------------------------------------
       2. LOAD ALL EVENTS FROM DB
    ------------------------------------------------- */
    private function load_events()
    {
        $query = qa_db_query_sub('SELECT * FROM ^email_events ORDER BY eventid ASC');
        return qa_db_read_all_assoc($query);
    }

    /* -------------------------------------------------
       3. SAVE (ADD/EDIT/DELETE)
    ------------------------------------------------- */
    private function save_events()
    {
        $count = (int) qa_post_text('event_count');

        /* Clear & rewrite all events (simplest for now) */
        qa_db_query_sub('DELETE FROM ^email_events');

        for ($i = 1; $i <= $count; $i++) {
            $title = trim(qa_post_text('user_title_'.$i));
            $subject = trim(qa_post_text('subject_key_'.$i));
            $forced = (int) qa_post_text('forced_'.$i);

            if ($key === '' || $title === '') continue;

            qa_db_query_sub(
                'INSERT INTO ^email_events (user_title, subject_key, forced) VALUES ($, $, $)',
                $title, $subject, $forced
            );
        }
        return true;
    }
	private function reset_default_events()
	{
		// Wipe the table
		qa_db_query_sub("DELETE FROM ^email_events");

		// Insert default rows
		foreach ($this->default_insert_queries() as $query) {
			qa_db_query_raw($query);
		}
	}


    // ADMIN FORM
    public function admin_form()
    {
        $saved = false;


		if (qa_clicked('reset_default_events')) {
			$this->reset_default_events();
			qa_redirect(qa_request(), ['ok' => 'Default email events restored successfully']);
		}
		
        if (qa_clicked('save_email_events')) {
            $saved = $this->save_events();
        }

        $events = $this->load_events();

        /* ---------------------------
           Build Editable Rows
        --------------------------- */
        $html = '<style>
				.ev-box { background:#fafafa;border:1px solid #ccc;padding:10px;margin-bottom:10px;border-radius:6px; }
				.ev-box h4 { margin:0 0 8px 0; }
				.ev-row { margin-bottom:6px; }
				.ev-label { display:block;font-weight:bold;margin-bottom:3px; }
				.ev-input { width:100%;padding:4px; }
				.ev-add { background:#007bff;color:#fff;padding:6px 10px;border:none;border-radius:4px;cursor:pointer; }
				.ev-remove { background:#dc3545;color:#fff;padding:4px 8px;border:none;border-radius:4px;margin-top:5px;cursor:pointer; }
			</style>

			<script>
				document.addEventListener("DOMContentLoaded", function(){
					const addBtn = document.getElementById("ev-add-btn");
					const cont = document.getElementById("ev-container");
					const cnt = document.getElementById("event_count");

					if (!addBtn || !cont || !cnt) return;

					addBtn.addEventListener("click", function(e){
						e.preventDefault();
						let i = parseInt(cnt.value) + 1;

						let d = document.createElement("div");
						d.className = "ev-box";
						d.innerHTML = `
							<h4>Event ${i}</h4>
							<div class="ev-row">
								<label class="ev-label">Title shown to user</label>
								<input class="ev-input" type="text" name="user_title_${i}">
							</div>
							<div class="ev-row">
								<label class="ev-label">Subject Key (optional, e.g. emails/q_answered_subject)</label>
								<input class="ev-input" type="text" name="subject_key_${i}">
							</div>
							<div class="ev-row">
								<label class="ev-label">Forced? (cannot be unsubscribed)</label>
								<select name="forced_${i}" class="ev-input">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select>
							</div>
							<button type="button" class="ev-remove">Remove</button>
						`;

						d.querySelector(".ev-remove").addEventListener("click", function(){ d.remove(); updateCount(); });

						cont.appendChild(d);
						cnt.value = i;
					});

					function updateCount(){
						const boxes = cont.querySelectorAll(".ev-box").length;
						cnt.value = boxes;
					}
				});
			</script>';


        /* Existing rows */
        $i = 1;
        $html .= '<div id="ev-container">';
        foreach ($events as $ev) {
            $html .= "
            <div class='ev-box'>
                <h4>Event {$i}</h4>
                <div class='ev-row'>
                    <label class='ev-label'>Title shown to user</label>
                    <input class='ev-input' type='text' name='user_title_{$i}' value='".qa_html($ev['user_title'])."'>
                </div>
                <div class='ev-row'>
                    <label class='ev-label'>Subject Key</label>
                    <input class='ev-input' type='text' name='subject_key_{$i}' value='".qa_html($ev['subject_key'])."'>
                </div>
                <div class='ev-row'>
                    <label class='ev-label'>Forced?</label>
                    <select name='forced_{$i}' class='ev-input'>
                        <option value='0'".($ev['forced']==0?' selected':'').">No</option>
                        <option value='1'".($ev['forced']==1?' selected':'').">Yes</option>
                    </select>
                </div>
                <button type='button' class='ev-remove' onclick='this.parentElement.remove()'>".qa_lang_html('emailopt/remove_event_button')."</button>
            </div>
            ";
            $i++;
        }
        $html .= '</div>';

        /* Add new */
        $html .= '<button id="ev-add-btn" class="ev-add">+ ' . qa_lang_html('emailopt/add_event_button') . '</button>';


        /* Return form */
        return [
				'ok' => $saved ? qa_lang_html('emailopt/events_saved') : null,

				'fields' => [
					[ 'type' => 'static', 'value' => $html ],
					[
						'type'  => 'hidden',
						'tags'  => 'id="event_count" name="event_count"',
						'value' => $i - 1
					]
				],

				'buttons' => [
					[
						'label' => qa_lang_html('emailopt/save_events'),
						'tags'  => 'name="save_email_events" class="ev-add"'
					],
					[
						'label' => qa_lang_html('emailopt/reset_defaults'),
						'tags'  => 'name="reset_default_events" 
									onclick="return confirm(\'' 
										. qa_lang_html('emailopt/reset_confirm') 
										. '\')"'
					],
				],
			];

    }
}

