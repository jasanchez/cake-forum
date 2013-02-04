<?php

$this->Breadcrumb->add(__d('forum', 'Users'), array('controller' => 'users', 'action' => 'index'));
$this->Breadcrumb->add($profile['User'][$config['userMap']['username']], $this->Forum->profileUrl($profile['User']));
$this->Breadcrumb->add(__d('forum', 'Report User'), array('action' => 'report', $profile['User']['id'])); ?>

<div class="title">
	<h2><?php echo __d('forum', 'Report User'); ?></h2>
</div>

<p>
	<?php printf(__d('forum', 'Are you sure you want to report the user %s? If so, please add a comment as to why you are reporting this user, and please be descriptive. Are they spamming, trolling, flaming, etc. 255 max characters.'),
		'<strong>' . $this->Html->link($profile['User'][$config['userMap']['username']], $this->Forum->profileUrl($profile['User'])) . '</strong>'); ?>
</p>

<?php echo $this->Form->create('Report'); ?>

<div class="container">
	<div class="containerContent">
		<?php echo $this->Form->input('comment', array('type' => 'textarea', 'label' => __d('forum', 'Comment'))); ?>
	</div>
</div>

<?php
echo $this->Form->submit(__d('forum', 'Report'), array('class' => 'button'));
echo $this->Form->end(); ?>