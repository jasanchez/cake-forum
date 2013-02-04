<?php
$this->Html->css('Utility.decoda-1.1.0.min', 'stylesheet', array('inline' => false));
$this->Html->css('Forum.decoda', 'stylesheet', array('inline' => false));
$this->Html->script('Utility.decoda-1.1.0.min', array('inline' => false)); ?>

<script type="text/javascript">
	window.addEvent('domready', function() {
		var decoda = new Decoda('<?php echo $id; ?>', {
			previewUrl: '/forum/posts/preview',
			onInitialize: function() {
				this.editor.getParent('div').addClass('input-decoda');
			},
			onSubmit: function() {
				return this.clean();
			},
			onRenderHelp: function(table) {
				table.addClass('table');
			}
		}).defaults();
	});
</script>