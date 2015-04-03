<?php

namespace Helper;

/**
 * Session Helper
 *
 * Automagically selects the correct backend, loads the current user,
 * and generates new session tokens.
 */
class Session extends \Prefab {

	/**
	 * Get a session model instance
	 * @param  int $user_id
	 * @param  bool $auto_save
	 * @return Session\SQL|Session\Jig
	 */
	public function getModel($user_id = null, $auto_save = true) {
		if(\Base::instance()->get("db.db_sessions") !== 0) {
			return new \Model\Session\SQL($user_id, $auto_save);
		} else {
			return new \Model\Session\Jig($user_id, $auto_save);
		}
	}

	/**
	 * Load the current session
	 * @return Session\SQL|Session\Jig
	 */
	public function getCurrent() {
		$f3 = \Base::instance();
		$token = $f3->get("COOKIE.{$this->cookie_name}");
		$model = $this->getModel();
		if($token) {
			$model->load(array("token = ?", $token));
			$expire = $f3->get("JAR.expire");

			// Delete expired sessions
			if(time() - $expire > strtotime($model->created)) {
				$model->delete();
				return $model;
			}

			// Update nearly expired sessions
			if(time() - $expire / 2 > strtotime($model->created)) {
				$model->created = date("Y-m-d H:i:s");
				$model->setCurrent();
			}
		}
		return $model;
	}

	/**
	 * Set the user's cookie to the given session
	 * @param  \DB\Cursor $model
	 * @return Session
	 */
	public function setCurrent(\DB\Cursor $model) {
		$f3 = \Base::instance();
		$f3->set("COOKIE.{$this->cookie_name}", $model->token, $f3->get("JAR.expire"));
		return $this;
	}

}
