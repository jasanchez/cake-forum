<?php
/**
 * @copyright	Copyright 2006-2013, Miles Johnson - http://milesj.me
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under the MIT License
 * @link		http://milesj.me/code/cakephp/forum
 */

App::uses('ForumAppModel', 'Forum.Model');

class Report extends ForumAppModel {

	/**
	 * Report types.
	 */
	const TOPIC = 1;
	const POST = 2;
	const USER = 3;

	/**
	 * DB Table.
	 *
	 * @var string
	 */
	public $useTable = 'reported';

	/**
	 * Belongs to.
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'Reporter' => array(
			'className' => FORUM_USER,
			'foreignKey' => 'user_id'
		),
		'Topic' => array(
			'className' => 'Forum.Topic',
			'foreignKey' => 'item_id',
			'conditions' => array('Report.itemType' => self::TOPIC)
		),
		'Post' => array(
			'className' => 'Forum.Post',
			'foreignKey' => 'item_id',
			'conditions' => array('Report.itemType' => self::POST)
		),
		'User' => array(
			'className' => FORUM_USER,
			'foreignKey' => 'item_id',
			'conditions' => array('Report.itemType' => self::USER)
		)
	);

	/**
	 * Behaviors.
	 *
	 * @var array
	 */
	public $actsAs = array(
		'Utility.Filterable' => array(
			'comment' => array('strip' => true)
		)
	);

	/**
	 * Validation.
	 *
	 * @var array
	 */
	public $validate = array(
		'comment' => 'notEmpty'
	);

	/**
	 * Enum.
	 *
	 * @var array
	 */
	public $enum = array(
		'itemType' => array(
			self::TOPIC => 'TOPIC',
			self::POST => 'POST',
			self::USER => 'USER'
		)
	);

	/**
	 * Get the latest reports.
	 *
	 * @param int $limit
	 * @return array
	 */
	public function getLatest($limit = 10) {
		return $this->find('all', array(
			'limit' => $limit,
			'order' => array('Report.created' => 'ASC'),
			'contain' => array('Reporter', 'Topic', 'Post', 'User')
		));
	}

}
