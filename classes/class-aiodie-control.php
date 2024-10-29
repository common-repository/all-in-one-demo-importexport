<?php

/**
 * A customizer control for rendering the export/import form.
 *
 * @since 0.1
 */
final class AIODIE_Control extends WP_Customize_Control {
	
	/**
	 * Renders the control content.
	 *
	 * @since 0.1
	 * @access protected
	 * @return void
	 */
	protected function render_content()
	{
		include AIODIE_PLUGIN_DIR . 'includes/control.php';
	}
}