<?php

if ($method === 'add') {
	$button = __d('forum', 'Save');
	$title = __d('forum', 'Add Forum');
} else {
	$button = __d('forum', 'Update');
	$title = __d('forum', 'Edit Forum');
}

$this->Breadcrumb->add(__d('forum', 'Administration'), array('controller' => 'forum', 'action' => 'index'));
$this->Breadcrumb->add(__d('forum', 'Forums'), array('controller' => 'stations', 'action' => 'index'));
$this->Breadcrumb->add($title, $this->here); ?>

<div class="title">
	<h2><?php echo $title; ?></h2>
</div>

<?php echo $this->Form->create('Forum'); ?>

<div class="container">
	<div class="containerContent">
		<?php
		echo $this->Form->input('title', array('label' => __d('forum', 'Title')));
		echo $this->Form->input('status', array('options' => $this->Forum->options('forumStatus'), 'label' => __d('forum', 'Status')));
		echo $this->Form->input('orderNo', array('style' => 'width: 50px', 'maxlength' => 2, 'label' => __d('forum', 'Order No')));
		echo $this->Form->input('forum_id', array('options' => $forums, 'label' => __d('forum', 'Forum'), 'empty' => '-- ' . __d('forum', 'None') . ' --', 'escape' => false)); ?>

		<div class="inputDivider"><?php echo __d('forum', 'The fields below apply to child forums.'); ?></div>

		<?php
		echo $this->Form->input('description', array('type' => 'textarea', 'label' => __d('forum', 'Description')));
		echo $this->Form->input('aro_id', array('options' => $this->Forum->options('accessGroups'), 'label' => __d('forum', 'Restrict Access To'), 'empty' => '-- ' . __d('forum', 'None') . ' --'));
		echo $this->Form->input('accessRead', array('options' => $this->Forum->options('status'), 'label' => __d('forum', 'Read Topics')));
		echo $this->Form->input('accessPost', array('options' => $this->Forum->options('status'), 'label' => __d('forum', 'Create Topics')));
		echo $this->Form->input('accessReply', array('options' => $this->Forum->options('status'), 'label' => __d('forum', 'Create Posts')));
		echo $this->Form->input('accessPoll', array('options' => $this->Forum->options('status'), 'label' => __d('forum', 'Create Polls')));
		echo $this->Form->input('settingPostCount', array('options' => $this->Forum->options(), 'label' => __d('forum', 'Increase Users Post/Topic Count')));
		echo $this->Form->input('settingAutoLock', array('options' => $this->Forum->options(), 'label' => __d('forum', 'Auto-Lock Inactive Topics'))); ?>
	</div>
</div>

<?php
echo $this->Form->submit($button, array('class' => 'button'));
echo $this->Form->end(); ?>