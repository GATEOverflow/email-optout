<?php

class qa_html_theme_layer extends qa_html_theme_base
{
    public function doctype()
    {
        if ($this->template === 'account') {
            $form = $this->email_prefs_generate();
            if ($form) {
                $this->content['form_emailprefs'] = $form;
            }
        }

        parent::doctype();
    }

    private function email_prefs_generate()
    {
        $userid = qa_get_logged_in_userid();
        if (!$userid) return null;

        require_once QA_INCLUDE_DIR . 'db/metas.php';

        //    SAVE HANDLER
        if (qa_clicked('save_emailprefs')) {

            $vals = qa_post_array('emailprefs'); // FIXED (array-safe)

            $csv = is_array($vals) ? implode(',', $vals) : '';
            qa_db_usermeta_set($userid, 'emailprefs', $csv);

            qa_redirect($this->request, ['email_ok' => '1']);
        }

        // LOAD EVENTS FROM DB
        $events = qa_db_read_all_assoc(
            qa_db_query_sub('SELECT subject_key, user_title, forced FROM ^email_events ORDER BY eventid ASC')
        );

        $manageable = [];
        $forced = [];

        foreach ($events as $ev) {
            if ((int)$ev['forced'] === 1)
                $forced[] = $ev;
            else
                $manageable[] = $ev;
        }

        // USER PREFERENCE LOAD
		$csv = qa_db_usermeta_get($userid, 'emailprefs');

		// Detect NEW user (no row exists in usermeta)
		$is_new_user = ($csv === null);

		// Detect user manually unsubscribed everything
		$is_user_disabled_all = ($csv === '');

		// Load saved preferences (if any)
		$saved = array_filter(explode(',', $csv));

        /* ------------------------------
            CSS + JS (modern UI)
        ------------------------------ */

        $html = '
				<style>

					/* Toast Notification */
					#em-toast {
						visibility:hidden;
						min-width:250px;
						background:#0f5132;
						color:white;
						text-align:center;
						padding:12px;
						border-radius:8px;
						position:fixed;
						z-index:99999;
						left:50%;
						transform:translateX(-50%);
						bottom:30px;
						font-size:14px;
						opacity:0;
						transition:opacity .4s ease, bottom .4s ease;
					}
					#em-toast.show {
						visibility:visible;
						opacity:1;
						bottom:60px;
					}

					/* Light mode (default) */
					:root {
						--em-bg: #fff;
						--em-text: #000;
						--em-border: #ccc;
						--em-hover: #f7f7f7;
					}

					/* OS-based Dark Mode */
					@media (prefers-color-scheme: dark) {
						:root {
							--em-bg:#1e1e1e;
							--em-text:#ddd;
							--em-border:#444;
							--em-hover:#333;
						}
					}

					/* Q2A theme dark mode */
					body.dark-mode :root,
					body.qa-dark :root,
					body.dark :root {
						--em-bg:#1e1e1e;
						--em-text:#ddd;
						--em-border:#444;
						--em-hover:#333;
					}


					.em-block {
						padding:10px;
						border:1px solid var(--em-border);
						background:var(--em-bg);
						border-radius:6px;
						margin-bottom:15px;
						color:var(--em-text);
					}

					.em-title {
						cursor:pointer;
						font-weight:bold;
						margin-bottom:8px;
						display:flex;
						justify-content:space-between;
						align-items:center;
						padding:6px;
					}

					.em-title:hover {
						background:var(--em-hover);
					}

					.em-arrow {
						width:18px;
						text-align:right;
						font-weight:bold;
					}

					.em-content {
						overflow:hidden;
						max-height:0;
						transition:max-height .35s ease;
						margin-left:10px;
					}

					.em-content.open {
						max-height:600px;
					}

					.em-search {
						width:98%;
						padding:6px;
						margin-bottom:8px;
						border:1px solid var(--em-border);
						border-radius:4px;
						background:var(--em-bg);
						color: var(--em-text);
					}

					.em-mini {
						padding:5px 10px;
						font-size:12px;
						cursor:pointer;
						border:1px solid var(--em-border);
						background:var(--em-hover);
						border-radius:4px;
						color:var(--em-text);
						margin-right:4px;
					}

					.forced-item {
						color:#888;
						margin:5px 0 5px 12px;
					}

				</style>

				<script>

				function toggleEM(id, header){
					var el = document.getElementById(id);
					var arrow = header.querySelector(".em-arrow");

					if (el.classList.contains("open")) {
						el.classList.remove("open");
						arrow.textContent = "▼";
					} else {
						el.classList.add("open");
						arrow.textContent = "▲";
					}
				}

				function em_select(cls, val) {
					document.querySelectorAll("." + cls).forEach(e => e.checked = val);
				}

				function em_filter(input, cls) {
					var txt = input.value.toLowerCase();
					document.querySelectorAll("." + cls).forEach(row => {
						row.style.display = row.innerText.toLowerCase().includes(txt) ? "block" : "none";
					});
				}

				function em_toast(msg){
					var t=document.getElementById("em-toast");
					t.textContent=msg;
					t.classList.add("show");
					setTimeout(()=>{ t.classList.remove("show"); }, 2500);
				}

				</script>

				<div id="em-toast"></div>
				';


        $html .= '
				<div class="em-block">
					<div class="em-title" onclick="toggleEM(\'emA\', this)">
						<span>'.qa_lang_html("emailopt/control_email_header").'</span>
						<span class="em-arrow">▼</span>
					</div>

					<div id="emA" class="em-content">

						<input type="text" class="em-search" placeholder="Search…" 
							   onkeyup="em_filter(this, \'em-row\')">

						<button type="button" class="em-mini" 
							onclick="em_select(\'em-check\', true)">Select All</button>

						<button type="button" class="em-mini" 
							onclick="em_select(\'em-check\', false)">Deselect All</button>
				';

        foreach ($manageable as $ev) {
            $subject_key = $ev['subject_key'];
            $checked = $is_new_user ? true : in_array($subject_key, $saved);

            $html .= '
						<div class="em-row">
							<label>
								<input type="checkbox" class="em-check" 
									name="emailprefs[]" value="'.qa_html($subject_key).'" 
									'.($checked ? 'checked' : '').'>
								'.qa_html($ev['user_title']).'
							</label>
						</div>';
        }

        $html .= '
					</div>
				</div>
				';


        $html .= '
				<div class="em-block">
					<div class="em-title" onclick="toggleEM(\'emB\', this)">
						<span>'.qa_lang_html("emailopt/mandatory_email_header").'</span>
						<span class="em-arrow">▼</span>
					</div>

					<div id="emB" class="em-content">
				';

        foreach ($forced as $ev) {
            $html .= '<div class="forced-item">• '.qa_html($ev['user_title']).'</div>';
        }

        $html .= '
					</div>
				</div>
				';

        //  Toast Notification Trigger
        if (qa_get('email_ok'))
            $html .= '<script> window.onload=function(){ em_toast("Settings saved."); } </script>';

        // Q2A FORM RETURN

        return [
            'title' => qa_lang_html('emailopt/email_notifications_header'),
            'tags'  => 'method="post" action="'.qa_self_html().'"',
            'style' => 'wide',
            'fields' => [
                ['type' => 'static', 'value' => $html]
            ],
            'buttons' => [
                [
                    'label' => qa_lang_html('emailopt/save_email_preferences'),
                    'tags'  => 'name="save_emailprefs"',
                ]
            ]
        ];
    }
}
