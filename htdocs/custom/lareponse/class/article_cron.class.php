<?php
/* Copyright (C) 2024  Ravi Trébuchet             <ravi@code42.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

dol_include_once('/lareponse/class/article.class.php');
dol_include_once('/lareponse/lib/lareponse.lib.php');
include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . "/cron/class/cronjob.class.php";

/**
 * Class to manage Articles Cron (send notification on article update)
 */
class ArticleCron
{
	/**
	 * Sender of notification, it should be LAREPONSE_NOTIFICATION_CHECK const
	 */
	private $sender = "";

	/**
	 * Final error message
	 */
	private $errorMessage = "";

	/**
	 * Final error message
	 */
	private $informationMessage = "";

	/**
	 * Send notification to "subscribed" (creator, commented or favorite) users when an article is modified or commented
	 *
	 * @return int
	 * @throws Exception
	 */
	public function notifySubscribedUsers()
	{
		global $langs;

		dol_syslog("Cron Articles (LaReponse) - START notifySubscribedUsers() function", LOG_DEBUG);


		// Errors counter
		$this->errors = 0;
		$this->informationMessage = "";
		$sentMails = 0;

		// If notifications are enabled
		if (getDolGlobalInt("LAREPONSE_NOTIFICATION_CHECK")) {
			// Get default sendeer email
			$this->sender = getDolGlobalString("MAIN_MAIL_EMAIL_FROM");
			if (!empty($this->sender)) {
				$usersToNotify = $this->prepareUsersToNotify();
				if ($usersToNotify == -1) {
					dol_syslog("Cron Articles (LaReponse) - END notifySubscribedUsers() function", LOG_DEBUG);
					return -1;
				}

				if (!empty($usersToNotify) && is_array($usersToNotify)) {
					$sentMails = $this->sendNotifications($usersToNotify);
				} // Users to notify not empty
			} else {
				dol_syslog("Cron Articles (LaReponse) - END notifySubscribedUsers() function - Default sender email not found", LOG_ERR);
				$this->error .= "<br>Auto sender email not found, <a href='" . dol_buildpath("/admin/mails.php", 1) . "'>Configure it</a>";
				return -3;
			}
		} else {
			dol_syslog("Cron Articles (LaReponse) - Disabled", LOG_INFO);
			$this->informationMessage = $langs->trans("LaReponseArticleCronDisabled");
		}

		// Error log
		if (!empty($this->errorMessage)) $this->error = "Several errors <span class='classfortooltip' title='" . $this->errorMessage . "'><span class='fas fa-info-circle  em088 opacityhigh' style=' vertical-align: middle; cursor: help'></span></span>";
		if ($this->errors > 0) {
			dol_syslog("Cron Articles (LaReponse) - END notifySubscribedUsers() function - Several errors (" . $this->errors . ") were detected, return code -2", LOG_ERR);
			return -2;
		}

		$this->output = $sentMails . " mail(s) sent";
		if (!empty($this->informationMessage)) $this->output .= "<span class='classfortooltip' title='" . $this->informationMessage . "'><span class='fas fa-info-circle em088 opacityhigh' style=' vertical-align: middle; cursor: help'></span></span>";

		dol_syslog("Cron Articles (LaReponse) - END notifySubscribedUsers() function", LOG_DEBUG);
		return 0;
	}

	/**
	 * Prepare users to notify as an array
	 *
	 * @return array|int return < 0 if error, else return an array oh users with article id's
	 * @throws Exception
	 */
	private function prepareUsersToNotify()
	{
		global $db, $langs;

		dol_syslog("Cron Articles (LaReponse) - START prepareUsersToNotify() function", LOG_DEBUG);

		$usersToNotify = array();

		// Get articles updated since LAREPONSE_TIME_BEFORE_NOTIFICATION_CHECK minutes
		$lastCron = $this->getLastCronExecution();
		if ($lastCron > 0) {
			$article = new Article($db);
			$event = new ActionComm($db);
			// Foreach articles, get "subscribed" users
			$events = getDatedArticleEvents($lastCron, ">");
			foreach ($events as $eventId => $eventValues) {
				if ($event->fetch($eventId)) {
					if ($lastCron < $event->datec) {
						if ($article->fetch($eventValues->fk_element)) {
							$articleId = $article->id;
							if (!empty($articleId)) {
								$articleOwner = $article->fk_user_creat; // Id of user who created the article
								$modifier = $eventValues->fk_user_author; // Id of user who modified article (owner of event)
								// Notify creator if someone else modified it
								if (!empty($articleOwner) && // Check is owner exists
									$articleOwner != $modifier &&  // User who modified is not owner
									(empty($usersToNotify[$articleOwner]) ||  // user is not yet notified (check to avoir warnings)
										!is_array($usersToNotify[$articleOwner]) || // user is not yet notified (check to avoir warnings)
										!in_array($articleId, $usersToNotify[$articleOwner]))) { // user is not yet notified
									// We have to check if $usersToNotify[$articleOwner] exists before using it
									$usersToNotify[$articleOwner][] = $articleId;
								}

								// Notify user who had this article in favorite (and who's not the modifier)
								$sql = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "lareponse_favorites WHERE fk_article = " . $articleId . " AND fk_user != " . $modifier;
								$resqlFav = $db->query($sql);
								if (!empty($resqlFav)) {
									while ($favorite = $db->fetch_object($resqlFav)) {
										$userFavorite = $favorite->fk_user;
										// If user to notify is not at the origin of modification AND if he is not already in the list, we add him
										if (!empty($userFavorite) &&
											empty($usersToNotify[$userFavorite]) || // user is not yet notified (check to avoir warnings)
											!is_array($usersToNotify[$userFavorite]) || // user is not yet notified (check to avoir warnings)
											!in_array($articleId, $usersToNotify[$userFavorite])) {// user is not yet notified
											// We have to check if $usersToNotify[$articleOwner] exists before using it
											$usersToNotify[$userFavorite][] = $articleId;
										}
									}
								} else {
									dol_syslog("Cron Articles (LaReponse) - Error : impossible to execute this query '$sql'", LOG_ERR);
									return -1;
								}

								// Notify people who commented on the article
								$sql = "SELECT fk_user_creat FROM " . MAIN_DB_PREFIX . "lareponse_comment WHERE fk_article = " . $articleId . " AND fk_user_creat != " . $modifier;
								$resqlComm = $db->query($sql);
								if (!empty($resqlComm)) {
									while ($comment = $db->fetch_object($resqlComm)) {
										$commenter = $comment->fk_user_creat;
										// If user to notify is not at the origin of modification AND if he is not already in the list, we add him
										if (!empty($commenter) &&
											(empty($usersToNotify[$commenter]) || // user is not yet notified (check to avoir warnings)
												!is_array($usersToNotify[$commenter]) || // user is not yet notified (check to avoir warnings)
												!in_array($articleId, $usersToNotify[$commenter]))) { // user is not yet notified
											// We have to check if $usersToNotify[$articleOwner] exists before using it
											$usersToNotify[$commenter][] = $articleId;
										}
									}
								} else {
									dol_syslog("Cron Articles (LaReponse) - END prepareUsersToNotify() function - Error : impossible to execute this query '$sql'", LOG_ERR);
									return -1;
								}
							} // Article id's not empty
						} else { // Fetch Article
							dol_syslog("Cron Articles (LaReponse) - Error : article cannot be loaded", LOG_WARNING);
							$this->errorMessage = $langs->trans("LareponseArticleCronErrorArticleCantBeLoaded");
						}
					} // Check time
				} else { // Fetch event
					dol_syslog("Cron Articles (LaReponse) - Error : event cannot be loaded", LOG_WARNING);
					$this->errorMessage = $langs->trans("LareponseArticleCronErrorEventCantBeLoaded");
				}
			} // For each event
		} else {
			dol_syslog("Cron Articles (LaReponse) - Error : cron is not well configured", LOG_WARNING);
			$this->errorMessage = "Sending article update notification cron is not well configured";
		}

		dol_syslog("Cron Articles (LaReponse) - END prepareUsersToNotify() function", LOG_DEBUG);
		return $usersToNotify;
	}

	/**
	 * Send notification to users
	 *
	 * @param  array    $usersToNotify    Users to notify with article updated
	 * @return int                            Number of mails sent
	 * @throws Exception
	 */
	private function sendNotifications($usersToNotify)
	{
		global $db, $langs, $conf;

		dol_syslog("Cron Articles (LaReponse) - START sendNotifications() function", LOG_DEBUG);

		// Get email template
		$templateId = getDolGlobalInt("LAREPONSE_EMAIL_TEMPLATE_FOR_NOTIFICATIONS");
		if (!empty($templateId)) {
			// Load template
			$template = getLaReponseEmailTemplate($templateId);
			// And get topic
			if (!empty($template)) $topic = $template->topic;
		} else {
			// If constant is not set, we get default template and its topic
			$template = getLaReponseEmailTemplate(getFirstArticleTemplate());
			$topic = $template->topic;
		}
		// If topic is empty (if constant is set with a wrong value) we get default tempalte and topic
		if (empty($topic)) {
			dol_syslog("Cron Articles (LaReponse) - Configured email template is wrong, default email template is loaded for this time but you should configure it correctly", LOG_WARNING);

			$template = getLaReponseEmailTemplate(getFirstArticleTemplate());
			$topic = $template->topic;
		}
		// We finally load content
		$content = $template->content;

		// If topic or content is empty (no template available, we return an error)
		if (!empty($topic) && !empty($content)) {
			$notifiedUser = new User($db);
			// Send notification to users to notify
			foreach ($usersToNotify as $userId => $articlesToSend) {
				$articlesToSend = array_flip($articlesToSend);
				if ($notifiedUser->fetch($userId) > 0) {
					// Continue only if user has email
					if (!empty($notifiedUser->email)) {
						// Send notification (and substitution)
						$substitutionArray = getCommonSubstitutionArray($langs);
						$substitutionArray["__LAREPONSE_ARTICLE_LIST_LINKS__"] = buildArticleLinks($articlesToSend); // Replace __LAREPONSE_ARTICLE_LIST_LINKS__ by article links list
						$contentToSend = make_substitutions(($content), $substitutionArray);
						// Create mail object and send mail
						$mailfile = new CMailFile(make_substitutions($topic, $substitutionArray), $notifiedUser->email, "<" . $this->sender . ">", $contentToSend, array(), array(), array(), '', '', 0, 1);
						$res = $mailfile->sendfile();
						if ($res) {
							$mails++;
						} else {
							dol_syslog("Cron Articles (LaReponse) - Mail not sent to " . $notifiedUser->email, LOG_WARNING);
							$this->errorMessage .= "<br>Mail not sent to " . $notifiedUser->email;
							$this->errors++;
						}
					} else {
						dol_syslog("Cron Articles (LaReponse) - User " . $notifiedUser->login . " does not have an email address", LOG_WARNING);
						$this->errorMessage .= "<br>User " . $notifiedUser->login . " does not have an email address";
						$this->informationMessage .= "<br>User " . $notifiedUser->login . " does not have an email address";
					}
				} else {
					dol_syslog("Cron Articles (LaReponse) - User with id " . $userId . " not found", LOG_WARNING);
					$this->errorMessage .= "<br>User with id " . $userId . " not found";
					$this->errors++;
				}
			} // Foreach users to notify
		} else {
			dol_syslog("Cron Articles (LaReponse) - Email template not found, email not sent", LOG_WARNING);
			$this->errorMessage .= "<br>Email template not found, id of constant : $templateId. There is no default template available.";
			$this->errors++;
		}

		dol_syslog("Cron Articles (LaReponse) - END sendNotifications() function", LOG_DEBUG);
		return $mails;
	}

	/**
	 * Get timestamp of last execution
	 *
	 * @return int
	 */
	private function getLastCronExecution()
	{
		dol_syslog("Cron Articles (LaReponse) - START getLastCronExecution() function", LOG_DEBUG);

		// Select cron information
		$cron = getArticleNotificationCronFrequency();
		$unit = $cron['unit'];
		$frequency = $cron['frequency'];
		// Last run calculation
		$lastExecution = dol_now() - ($unit * $frequency);

		dol_syslog("Cron Articles (LaReponse) - END getLastCronExecution() function", LOG_DEBUG);
		return $lastExecution;
	}
}
