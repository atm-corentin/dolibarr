<?php
/* Copyright (C) 2022 Fabien FERNANDES ALVES <fabien@code42.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    h2g2/class/actions_h2g2.class.php
 * \ingroup h2g2
 * \brief   Hook overload class.
 */

/**
 * Class ActionsH2G2
 */
class ActionsH2G2
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Print the left block with menus
	 *
	 * @param  array $parameters Hook metadatas (context, etc...)
	 * @return int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printLeftBlock($parameters)
	{
		global $conf, $langs;

		$langs->load('h2g2@h2g2');
		if (property_exists($conf->global, 'H2G2_DISABLE_MODULE_WIZARD') && !$conf->global->H2G2_DISABLE_MODULE_WIZARD && strpos($parameters['context'], 'adminmodules') !== false) {
			dol_include_once('h2g2/lib/h2g2.lib.php');
			$modules = getH2G2ModulesNotUpToDate();

			if (count($modules) > 0) {
				$module = $modules[0]; // We only take the first module

				// Add the robot
				$out = '<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>';
				$out .= '<link href="https://fonts.googleapis.com/css?family=Baloo+2:400,800&display=swap" rel="stylesheet">';
				$msg = '<div id="h2g2wizard">';
				$msg .= '<div class="h2g2wizard__close"><span id="h2g2wizardclose" class="h2g2wizard__close-circle"><i class="fas fa-times h2g2wizard__close-icon"></i></span></div>';
				$msg .= '<div class="bubble">';
				$msg .= $langs->trans('H2G2UpdateAvailable', $module['fullname']) . '<br/><br/>' . $langs->trans('H2G2UpdateAvailableReactivate');
				$msg .= '</div>';
				$msg .= '<lottie-player src="' . dol_buildpath('/h2g2/assets/wizard.json', 1) . '" mode="bounce" background="transparent"  speed="1" autoplay loop></lottie-player>';
				$msg .= '</div>';

				$out .= '<script type="text/javascript">
                    $(document).ready(function() {
                        $( "#id-right .fiche" ).before( \'' . $msg . '\' );
                        const wizard = document.querySelector(\'#h2g2wizard\');
                        if (wizard) {
                            const link = document.querySelector(\'a[href*="value=' . $module['className'] . '"]\');
                            let highlight = null;
                            let overlay = null;
                            if (link) {
                                const container = link.closest(\'.info-box\');
                                if (container) {    
                                    // Auto scroll to div on click
                                    wizard.addEventListener(\'click\', (e) => {
                                        // Avoid scroll on click on the times icon
                                        if (!e.target.classList.contains("h2g2wizard__close-icon") && !e.target.classList.contains("h2g2wizard__close-circle")) {
                                            const y = container.getBoundingClientRect().top + window.scrollY;
                                            window.scrollTo({ top: y, behavior: \'smooth\'});   
                                        }       
                                    });
                                    const size = container.getBoundingClientRect();
                                    
                                    // Scroll to link to box about the module
                                    const y = container.getBoundingClientRect().top + window.scrollY;
                                    window.scrollTo({ top: y, behavior: \'smooth\'});
                                    
                                    const body = document.querySelector(\'body#mainbody\');
                                    overlay = document.createElement(\'div\');
                                    overlay.style.background = "black";
                                    overlay.style.transition = "all .3s ease-out";
                                    overlay.style.opacity = "0";
                                    overlay.style.zIndex = "1002"; // To be over the navbar
                                    overlay.style.height = "100%";
                                    overlay.style.width = "100%";
                                    overlay.id = "h2g2wizard-overlay";
                                    body.appendChild(overlay);
                                    
                                    highlight = document.createElement(\'div\');
                                    highlight.style.zIndex = "1003"; // To be over the overlay
                                    highlight.style.boxShadow = "rgb(33 33 33 / 80%) 0px 0px 1px 2px, rgb(33 33 33 / 50%) 0px 0px 0px 5000px";
                                    highlight.style.opacity = "1";
                                    highlight.style.height = size.height.toString() + \'px\';
                                    highlight.style.width = size.width.toString() + \'px\';
                                    highlight.style.position = "absolute";
                                    highlight.style.top = (size.top + window.scrollY).toString()+ \'px\';
                                    highlight.style.left = size.left.toString() + \'px\';
                                    highlight.style.pointerEvents = \'none\';
                                    highlight.id = "h2g2wizard-overlay-hilight";
                                    body.appendChild(highlight);
                                }
                            }
                                
                            // Manage close wizard
                            const closeBtn = document.querySelector("#h2g2wizardclose");
                            if (closeBtn) {
                                closeBtn.addEventListener(\'click\', e => {
                                    e.preventDefault();
                                    if (wizard) {
                                        wizard.remove();                                        
                                    }
                                    
                                    if (highlight) {
                                        highlight.remove();
                                    }
                                    
                                    if (overlay) {
                                        overlay.remove();
                                    }
                                })
                            }
                        }
                    });
                </script>';
				$this->resprints = $out;
			}
		}

		return 0;
	}

	/**
	 * Hook to add more HTML header. This hook will be used to include php library only
	 *
	 * @return int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addHtmlHeader()
	{
		global $conf, $user, $db;

		// [#35] Include dev library globally
		if (property_exists($conf->global, 'H2G2_INCLUDE_DEV_LIB') && $conf->global->H2G2_INCLUDE_DEV_LIB) {
			dol_include_once('/h2g2/lib/dev.lib.php');
		}


		if ($user->id > 0) {
			$date = $db->idate(dol_now());

			// get id of news not read yet
			$sql = "SELECT fk_object FROM " . MAIN_DB_PREFIX . "actioncomm_extrafields";
			$sql .= " WHERE viewed_by_user IS NULL";
			$sql .= " OR viewed_by_user NOT LIKE '%\"" . $user->id . "\"%'";
			$res = $db->query($sql);
			$ids = array();
			if ($res && $db->num_rows($res) > 0) {
				while ($result = $db->fetch_object($res)) {
					$ids[] = $result->fk_object;
				}
			}

			if (!empty($ids)) {
				//news in progress
				$sql = "SELECT id, label as title, note as content, code FROM " . MAIN_DB_PREFIX . "actioncomm";
				$sql .= " WHERE code LIKE 'H2G2News%'";
				$sql .= " AND datep <= '" . $date . "'";
				$sql .= " AND datep2 >= '" . $date ."'";
				$sql .= " AND id IN (" . implode(',', $ids) . ")";
				$sql .= " AND entity IN (0, " . $conf->entity . ")";
				$sql .= " ORDER BY datep ASC";
				$res = $db->query($sql);

				if ($res && $db->num_rows($res) > 0) {
					while ($new = $db->fetch_object($res)) {
						$title = $new->title;
						$content = $new->content;
						$news_id = $new->id;
						$canDeleteBar = 1;

						//get new's code to apply the correct color on the news
						switch ($new->code) {
							case 'H2G2NewsSucc':
								$code = 'success';
								break;
							case 'H2G2NewsInfo':
								$code = 'utility';
								break;
							case 'H2G2NewsWarn':
								$code = 'warning';
								break;
							case 'H2G2NewsDang':
								$code = 'danger';
								$canDeleteBar = 0;
								break;
						}

						// Remove 3 dots added by the creation of the actioncomm if the string length is higher than 128 characters
						// then cut the string to get readable string for swal (this is used principally when user put emojis in title)
						if (strlen($title) == '131') {
							$title = substr($title, 0, -3);
							$tmp = strrev($title);
							$title = strrev(substr($tmp, strpos($tmp, "\\") + 1));
						}

						$topBar = '<div id="news-' . $news_id . '" class="top-info top-info-' . $code . '">';
						$topBar .= '<div class="top-info-content">';
						$topBar .= '<i class="fas fa-comments"></i>';
						$topBar .= '<div class="top-info-content-text">';
						$topBar .= '<h3 class="top-info-title">';
						$topBar .= $title;
						$topBar .= '</h3><div class="top-info-description">';
						$topBar .= $content;
						$topBar .= '</div>';
						$topBar .= '</div>';
						$topBar .= '</div>';
						if ($canDeleteBar) $topBar .= '<a href="#" onClick="closeTopInfo(this, ' . $user->id . ', ' . $news_id . ')" class="top-info-close"><i class="fa fa-close"></i></a>';
						$topBar .= '</div>';

						print $topBar;

						print '<script>
                        function closeTopInfo(closeBtn, userid, newsid) {
                            $.ajax({
							    url: "' . dol_buildpath('/h2g2/ajax/set_top_info_viewed_user.php', 1) . '",
							    type: "POST",
							    data: { id: userid, news_id: newsid},
							    success: function(data) {
                                   $(closeBtn).closest("div#news-" + newsid).remove()
							    }
					        });
                        }
                    </script>';
					}
				}
			}

			//get news finished
			$sql = "SELECT id FROM " . MAIN_DB_PREFIX . "actioncomm ";
			$sql .= "WHERE code LIKE 'H2G2News%' ";
			$sql .= "AND datep2 < '" . $date . "'";
			$res = $db->query($sql);
			$ids = array();
			if ($res && $db->num_rows($res) > 0) {
				while ($new = $db->fetch_object($res)) {
					$ids[] = $new->id;
				}
			}

			if (!empty($ids)) {
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "actioncomm_extrafields WHERE fk_object IN (" . implode(',', $ids) . ")";
				$db->query($sql);
			}
		}


		// Include Material Icons
		$this->resprints = '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';

		return 0;
	}

	/**
	 * Prompt the latest news if the user didn't see it
	 *
	 * @param  $parameters      array  Contains context and others paramaters
	 * @param  $users           Object User
	 * @param  $action          string Current action
	 * @return void
	 */
	public function printTopRightMenu($parameters, $users, $action)
	{
		global $db, $user, $langs, $conf;

		$permissiontoadd = -1;
		// [#41] Request to know if the user got last news viewed at 0 (so we prompt news)
		$sql = "SELECT news_viewed FROM " . MAIN_DB_PREFIX . "user_extrafields WHERE fk_object = " . $user->id;
		$res = $this->db->query($sql);

		if ($res) {
			$obj = $db->fetch_object($res);
			$permissiontoadd = $obj->news_viewed;
		}

		if ($permissiontoadd == null || $permissiontoadd == '0') {
			$sql = "SELECT id as id, label as title, note as content, fk_element as article_id FROM " . MAIN_DB_PREFIX . "actioncomm WHERE code = 'C42_NEWS' ORDER BY id DESC LIMIT 1";
			$res = $this->db->query($sql);

			if ($res && $db->num_rows($res) > 0) {
				$obj = $db->fetch_object($res);
				$title = $obj->title;
				$content = $obj->content;
				$news_id = $obj->id;
				if (!empty($obj->article_id)) {
					$res = $db->query("SELECT content, entity, type FROM " . MAIN_DB_PREFIX . "lareponse_article WHERE rowid = " . $obj->article_id);
					if ($res && $db->num_rows($res) > 0) {
						$article = $db->fetch_object($res);
						if ($conf->entity == $article->entity) {
							if ($article->type) $content .= '<br /><br /><iframe src="' . $article->content . '" frameborder="0" width="100%" height="800" allowtransparency ></iframe>';
							else $content .= '<br /><br />' . $article->content;
						}
					}
				}
				// [#41] Remove 3 dots added by the creation of the actioncomm if the string length is higher than 128 characters
				// then cut the string to get readable string for swal (this is used principally when user put emojis in title)
				if (strlen($title) == '131') {
					$title = substr($title, 0, -3);
					$tmp = strrev($title);
					$title = strrev(substr($tmp, strpos($tmp, "\\") + 1));
				}

				print '<script>';
				print '$(document).ready(function() {
                                    Swal.fire({
                                        title: "' . $title. '",
                                        html: `' . $content . '`,
                                        allowOutsideClick: false,
                                        width: "80%",
                                        customClass: {
                                            content: "h2g2-news-content"
                                        },
                                        didOpen: function(element) {
                                        
                                            let content = document.getElementById("swal2-content");
                                            let el = document.querySelector(".h2g2-news-content");
                                            
                                            el.scrollTop = 0;
                                            
                                            if (content.offsetHeight > element.offsetHeight) {
                                                el.style.scrollBehavior = "smooth";
                                                Swal.disableButtons();
                                            
                                                const btnDiv = document.createElement("div");
                                                btnDiv.classList.add("div-btn-scroll");
                                            
                                               const btntop = Object.assign(document.createElement("button"), {
                                                    innerHTML: "<span class=\"fas fa-chevron-up\"></span>",
                                                    className: "swal2-styled swal2-confirm",
                                                    style: "display: none;",
                                                    onclick: () => {
                                                        el.scrollTop -= (el.clientHeight - 16);
                                                    }
                                                });

                                                const btnbot = Object.assign(document.createElement("button"), {
                                                    innerHTML: "<span class=\"fas fa-chevron-down\"></span>",
                                                    className: "swal2-styled swal2-confirm",
                                                    style: "opacity: 1",
                                                    onclick: () => {
                                                        el.scrollTop += (el.clientHeight - 16);
                                                    }
                                                });
                                               
                                                btnDiv.appendChild(btntop);
                                                btnDiv.appendChild(btnbot);
                                                
                                                el.appendChild(btnDiv);
                                                
                                                el.onscroll = function() {
                                                    if (el.scrollTop == 0) {
                                                        btntop.style.display = "none";
                                                        btnbot.style.opacity = "1";
                                                    } else if (Math.ceil(el.scrollTop + el.clientHeight) < el.scrollHeight) {
                                                        btntop.style.display = "inline-block";
                                                        btnbot.style.display = "inline-block";
                                                        btnbot.style.opacity = "";
                                                    } else {
                                                        btnbot.style.display = "none";
                                                        Swal.enableButtons();
                                                    }
                                                }
                                            }
                                        }
                                    }).then((result) => {
                                    if (result.isConfirmed) {
                                        $.ajax({
                                            url: "' . dol_buildpath('/h2g2/ajax/set_news_viewed_user.php', 1) . '",
                                            type: "POST",
                                            data: { id:' . $user->id . ', news_id:' . $news_id . '},
                                            success: function(data) {
                                            console.log(data);
                                            }
                                       })
					                }
                                });
                            });';
				print '</script>';
			}
		}

		return 0;
	}

	/**
	 * Reset all users at 0 for extrafields news viewed when dolibarr is updated
	 *
	 * @param  $parameters      array  Contains context and others paramaters
	 * @param  $users           Object User
	 * @param  $action          string Current action
	 * @return resource
	 */
	public function doUpgrade2($parameters, $users, $action)
	{
		global $db;

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'user_extrafields SET news_viewed = 0';
		$res = $db->query($sql);

		return $res;
	}
}
