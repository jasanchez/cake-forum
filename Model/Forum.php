<?php
/**
 * @copyright	Copyright 2006-2013, Miles Johnson - http://milesj.me
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under the MIT License
 * @link		http://milesj.me/code/cakephp/forum
 */

App::uses('ForumAppModel', 'Forum.Model');

class Forum extends ForumAppModel {

	/**
	 * Behaviors.
	 *
	 * @var array
	 */
	public $actsAs = array(
		'Utility.Sluggable' => array(
			'length' => 100
		)
	);

	/**
	 * Belongs to.
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'Parent' => array(
			'className' => 'Forum.Forum',
			'foreignKey' => 'forum_id',
			'fields' => array('Parent.id', 'Parent.title', 'Parent.slug', 'Parent.forum_id')
		),
		'LastTopic' => array(
			'className' => 'Forum.Topic',
			'foreignKey' => 'lastTopic_id'
		),
		'LastPost' => array(
			'className' => 'Forum.Post',
			'foreignKey' => 'lastPost_id'
		),
		'LastUser' => array(
			'className' => FORUM_USER,
			'foreignKey' => 'lastUser_id'
		)
	);

	/**
	 * Has many.
	 *
	 * @var array
	 */
	public $hasMany = array(
		'Topic' => array(
			'className' => 'Forum.Topic',
			'dependent' => false
		),
		'Children' => array(
			'className' => 'Forum.Forum',
			'foreignKey' => 'forum_id',
			'order' => array('Children.orderNo' => 'ASC'),
			'dependent' => false
		),
		'SubForum' => array(
			'className' => 'Forum.Forum',
			'foreignKey' => 'forum_id',
			'order' => array('SubForum.orderNo' => 'ASC'),
			'dependent' => false
		),
		'Moderator' => array(
			'className' => 'Forum.Moderator',
			'dependent' => true,
			'exclusive' => true
		),
		'Subscription' => array(
			'className' => 'Forum.Subscription',
			'exclusive' => true,
			'dependent' => true
		)
	);

	/**
	 * Validate.
	 *
	 * @var array
	 */
	public $validate = array(
		'title' => 'notEmpty',
		'description' => 'notEmpty',
		'orderNo' => array(
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'Please supply a number'
			),
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'This setting is required'
			)
		)
	);

	/**
	 * Enum.
	 *
	 * @var array
	 */
	public $enum = array();

	/**
	 * Update all forums by going up the parent chain.
	 *
	 * @param int $id
	 * @param array $data
	 * @return void
	 */
	public function chainUpdate($id, array $data) {
		$this->id = $id;
		$this->save($data, false, array_keys($data));

		$forum = $this->getById($id);

		if ($forum['Forum']['forum_id'] != 0) {
			$this->chainUpdate($forum['Forum']['forum_id'], $data);
		}
	}

	/**
	 * Get a forum.
	 *
	 * @param string $slug
	 * @return array
	 */
	public function getBySlug($slug) {
		return $this->find('first', array(
			'conditions' => array(
				'Forum.accessRead' => self::YES,
				'Forum.slug' => $slug
			),
			'contain' => array(
				'Parent',
				'SubForum' => array(
					'conditions' => array(
						'SubForum.accessRead' => self::YES,
						'SubForum.aro_id' => $this->Session->read('Forum.groups')
					),
					'LastTopic', 'LastPost', 'LastUser'
				),
				'Moderator' => array('User')
			),
			'cache' => array(__METHOD__, $slug)
		));
	}

	/**
	 * Get the list of forums for the board index.
	 *
	 * @return array
	 */
	public function getAdminIndex() {
		return $this->find('all', array(
			'order' => array('Forum.orderNo' => 'ASC'),
			'conditions' => array('Forum.forum_id' => 0),
			'contain' => array('Children' => array('SubForum'))
		));
	}

	/**
	 * Get a grouped hierarchy.
	 *
	 * @param int $exclude
	 * @return array
	 */
	public function getGroupedHierarchy($exclude = null) {
		$conditions = array(
			'Forum.status' => self::OPEN,
			'Forum.accessRead' => self::YES
		);

		if (is_numeric($exclude)) {
			$conditions['Forum.id !='] = $exclude;
		}

		$forums = $this->find('all', array(
			'fields' => array('Forum.id', 'Forum.title', 'Forum.forum_id', 'Forum.orderNo'),
			'conditions' => $conditions,
			'order' => array('Forum.orderNo' => 'ASC'),
			'contain' => false
		));

		$root = array();
		$categories = array();
		$hierarchy = array();

		foreach ($forums as $forum) {
			if ($forum['Forum']['forum_id'] == 0) {
				$root[] = $forum['Forum'];
			} else {
				$categories[$forum['Forum']['forum_id']][$forum['Forum']['orderNo']] = $forum['Forum'];
			}
		}

		foreach ($root as $forum) {
			if (isset($categories[$forum['id']])) {
				$hierarchy[$forum['title']] = $this->_buildOptions($categories, $forum);
			}
		}

		return $hierarchy;
	}

	/**
	 * Get the hierarchy.
	 *
	 * @param bool $drill
	 * @param int $exclude
	 * @return array
	 */
	public function getHierarchy($drill = false, $exclude = null) {
		$conditions = array();

		if (is_numeric($exclude)) {
			$conditions = array(
				'Forum.id !=' => $exclude,
				'Forum.forum_id !=' => $exclude
			);
		}

		$forums = $this->find('all', array(
			'conditions' => $conditions,
			'fields' => array('Forum.id', 'Forum.title', 'Forum.forum_id'),
			'order' => array('Forum.orderNo' => 'ASC'),
			'contain' => false
		));

		$root = array();
		$categories = array();
		$hierarchy = array();

		foreach ($forums as $forum) {
			if ($forum['Forum']['forum_id'] == 0) {
				$root[] = $forum['Forum'];
			} else {
				$categories[$forum['Forum']['forum_id']][] = $forum['Forum'];
			}
		}

		foreach ($root as $forum) {
			$hierarchy[$forum['id']] = $forum['title'];
			$hierarchy += $this->_buildOptions($categories, $forum, $drill, 1);
		}

		return $hierarchy;
	}

	/**
	 * Get the list of forums for the board index.
	 *
	 * @return array
	 */
	public function getIndex() {
		$groups = (array) $this->Session->read('Forum.groups');

		return $this->find('all', array(
			'order' => array('Forum.orderNo' => 'ASC'),
			'conditions' => array(
				'Forum.forum_id' => 0,
				'Forum.status' => self::OPEN,
				'Forum.accessRead' => self::YES,
				'Forum.aro_id' => $groups
			),
			'contain' => array(
				'Children' => array(
					'conditions' => array(
						'Children.accessRead' => self::YES,
						'Children.aro_id' => $groups
					),
					'SubForum' => array(
						'fields' => array('SubForum.id', 'SubForum.title', 'SubForum.slug'),
						'conditions' => array(
							'SubForum.accessRead' => self::YES,
							'SubForum.aro_id' => $groups
						)
					),
					'LastTopic', 'LastPost', 'LastUser'
				)
			),
			'cache' => __METHOD__
		));
	}

	/**
	 * Move all categories to a new forum.
	 *
	 * @param int $start_id
	 * @param int $moved_id
	 * @return bool
	 */
	public function moveAll($start_id, $moved_id) {
		return $this->updateAll(
			array('Forum.forum_id' => $moved_id),
			array('Forum.forum_id' => $start_id)
		);
	}

	/**
	 * Update the order of the forums.
	 *
	 * @param array $data
	 * @return bool
	 */
	public function updateOrder($data) {
		if (isset($data['_Token'])) {
			unset($data['_Token']);
		}

		if ($data) {
			foreach ($data as $model => $fields) {
				foreach ($fields as $field) {
					$order = $field['orderNo'];

					if (!is_numeric($order)) {
						$order = 0;
					}

					$this->id = $field['id'];
					$this->save(array('orderNo' => $order), false, array('orderNo'));
				}
			}
		}

		return true;
	}

	/**
	 * Build the list of select options.
	 *
	 * @param array $categories
	 * @param array $forum
	 * @param bool $drill
	 * @param int $depth
	 * @return array
	 */
	protected function _buildOptions($categories, $forum, $drill = true, $depth = 0) {
		$options = array();

		if (isset($categories[$forum['id']])) {
			$children = $categories[$forum['id']];
			ksort($children);

			foreach ($children as $child) {
				$options[$child['id']] = str_repeat(' - ', ($depth * 4)) . $child['title'];

				if (isset($categories[$child['id']]) && $drill) {
					$babies = $this->_buildOptions($categories, $child, $drill, ($depth + 1));

					if ($babies) {
						$options = $options + $babies;
					}
				}
			}
		}

		return $options;
	}

}
