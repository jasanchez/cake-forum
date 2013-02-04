<?php
/**
 * @copyright	Copyright 2006-2013, Miles Johnson - http://milesj.me
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under the MIT License
 * @link		http://milesj.me/code/cakephp/forum
 */

class ForumToolbarComponent extends Component {

	/**
	 * Components.
	 *
	 * @var array
	 */
	public $components = array('Session');

	/**
	 * Controller instance.
	 *
	 * @var Controller
	 */
	public $Controller;

	/**
	 * Store the Controller.
	 *
	 * @param Controller $Controller
	 * @return void
	 */
	public function initialize(Controller $Controller) {
		$this->Controller = $Controller;
	}

	/**
	 * Initialize the session and all data.
	 *
	 * @param Controller $Controller
	 * @return void
	 */
	public function startup(Controller $Controller) {
		$this->Controller = $Controller;

		if ($this->Session->check('Forum.isBrowsing')) {
			return;
		}

		$user_id = $this->Controller->Auth->user('id');
		$banned = ($this->Controller->Auth->user(Configure::read('Forum.userMap.status')) == Configure::read('Forum.statusMap.banned'));
		$lastVisit = date('Y-m-d H:i:s');
		$isAdmin = false;
		$isSuper = false;
		$groups = array(0); // 0 is everything else
		$moderates = array();
		$permissions = array(
			'topics' => array(
				'create' => true,
				'read' => true,
				'update' => true,
				'delete' => true
			),
			'posts' => array(
				'create' => true,
				'read' => true,
				'update' => true,
				'delete' => true
			),
			'polls' => array(
				'create' => true,
				'read' => true,
				'update' => true,
				'delete' => true
			)
		);

		if ($user_id && !$banned) {
			$profile = ClassRegistry::init('Forum.Profile')->getUserProfile($user_id);
			$lastVisit = $profile['Profile']['lastLogin'];

			// Generate permissions list
			if ($access = ClassRegistry::init('Forum.Access')->getPermissions($user_id)) {
				$aroMap = Configure::read('Forum.aroMap');

				foreach ($access as $perm) {
					if ($perm['Aro']['alias'] === $aroMap['admin'] && !$isAdmin) {
						$isAdmin = true;
					}

					if ($perm['Aro']['alias'] === $aroMap['superMod'] && !$isSuper) {
						$isSuper = true;
					}

					// Save group IDs
					$groups[] = (int) $perm['Aro']['id'];

					// Save permissions
					foreach ($perm['Permission'] as $action => $can) {
						if (substr($action, 0, 1) !== '_') {
							continue;
						}

						$permissions[str_replace('forum.', '', $perm['Aco']['alias'])][str_replace('_', '', $action)] = (bool) $can;
					}
				}
			}

			// Save more data if they are admin
			if ($isAdmin) {
				$groups = array_merge($groups, array_keys(ClassRegistry::init('Forum.Access')->getList()));
			}

			// Get moderated forum IDs
			$moderates = ClassRegistry::init('Forum.Moderator')->getModerations($user_id);

		// If not logged in or banned
		} else {
			$permissions = false;
		}

		$this->Session->write('Forum.isAdmin', $isAdmin);
		$this->Session->write('Forum.isSuper', $isSuper);
		$this->Session->write('Forum.groups', array_values(array_unique($groups)));
		$this->Session->write('Forum.permissions', $permissions);
		$this->Session->write('Forum.moderates', $moderates);
		$this->Session->write('Forum.lastVisit', $lastVisit);
		$this->Session->write('Forum.isBrowsing', true);
	}

	/**
	 * Calculates the page to redirect to.
	 *
	 * @param int $topic_id
	 * @param int $post_id
	 * @param bool $return
	 * @return mixed
	 */
	public function goToPage($topic_id = null, $post_id = null, $return = false) {
		$topic = ClassRegistry::init('Forum.Topic')->getById($topic_id);
		$slug = !empty($topic['Topic']['slug']) ? $topic['Topic']['slug'] : null;

		// Certain page
		if ($topic_id && $post_id) {
			$posts = ClassRegistry::init('Forum.Post')->getIdsForTopic($topic_id);
			$perPage = Configure::read('Forum.settings.postsPerPage');
			$totalPosts = count($posts);

			if ($totalPosts > $perPage) {
				$totalPages = ceil($totalPosts / $perPage);
			} else {
				$totalPages = 1;
			}

			if ($totalPages <= 1) {
				$url = array('plugin' => 'forum', 'controller' => 'topics', 'action' => 'view', $slug, '#' => 'post-' . $post_id);
			} else {
				$posts = array_values($posts);
				$flips = array_flip($posts);
				$position = $flips[$post_id] + 1;
				$goTo = ceil($position / $perPage);
				$url = array('plugin' => 'forum', 'controller' => 'topics', 'action' => 'view', $slug, 'page' => $goTo, '#' => 'post-' . $post_id);
			}

		// First post
		} else if ($topic_id && !$post_id) {
			$url = array('plugin' => 'forum', 'controller' => 'topics', 'action' => 'view', $slug);

		// None
		} else {
			$url = $this->Controller->referer();

			if (!$url || strpos($url, 'delete') !== false) {
				$url = array('plugin' => 'forum', 'controller' => 'forum', 'action' => 'index');
			}
		}

		if ($return) {
			return $url;
		}

		return $this->Controller->redirect($url);
	}

	/**
	 * Simply marks a topic as read.
	 *
	 * @param int $topic_id
	 * @return void
	 */
	public function markAsRead($topic_id) {
		$readTopics = (array) $this->Session->read('Forum.readTopics');
		$readTopics[] = $topic_id;

		$this->Session->write('Forum.readTopics', array_unique($readTopics));
	}

	/**
	 * Updates the session topics array.
	 *
	 * @param int $topic_id
	 * @return void
	 */
	public function updateTopics($topic_id) {
		$topics = $this->Session->read('Forum.topics');

		if ($topic_id) {
			if (is_array($topics)) {
				$topics[$topic_id] = time();
			} else {
				$topics = array($topic_id => time());
			}

			$this->Session->write('Forum.topics', $topics);
		}
	}

	/**
	 * Updates the session posts array.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function updatePosts($post_id) {
		$posts = $this->Session->read('Forum.posts');

		if ($post_id) {
			if (is_array($posts)) {
				$posts[$post_id] = time();
			} else {
				$posts = array($post_id => time());
			}

			$this->Session->write('Forum.posts', $posts);
		}
	}

	/**
	 * Do we have access to commit this action.
	 *
	 * @param array $validators
	 * @return bool
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 * @throws ForbiddenException
	 */
	public function verifyAccess($validators = array()) {

		// Does the data exist?
		if (isset($validators['exists'])) {
			if (empty($validators['exists'])) {
				throw new NotFoundException();
			}
		}

		// Admins have full control
		if ($this->Session->read('Forum.isAdmin') || $this->Session->read('Forum.isSuper')) {
			return true;
		}

		// Are we a moderator? Grant access
		if (isset($validators['moderate'])) {
			if (in_array($validators['moderate'], $this->Session->read('Forum.moderates'))) {
				return true;
			}
		}

		// Is the item locked/unavailable?
		if (isset($validators['status'])) {
			foreach ((array) $validators['status'] as $status) {
				if (!$status) {
					throw new ForbiddenException();
				}
			}
		}

		// Does the user own this item?
		if (isset($validators['ownership'])) {
			if ($this->Controller->Auth->user('id') != $validators['ownership']) {
				throw new UnauthorizedException();
			}
		}

		return true;
	}

}
