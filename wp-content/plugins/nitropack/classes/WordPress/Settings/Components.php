<?php

namespace NitroPack\WordPress\Settings;
/**
 * Class Components
 *
 * This class handles the settings components for the NitroPack plugin in WordPress.
 *
 * @package NitroPack\WordPress\Settings
 */
class Components {

	/**
	 * @var string $plugin_dir_url The URL of the plugin directory.
	 */
	private $plugin_dir_url;
	/**
	 * Components constructor.
	 *
	 * Initializes the plugin directory URL.
	 */
	public function __construct() {
		$this->plugin_dir_url = plugin_dir_url( NITROPACK_FILE );
	}
	/**
	 * Get the URL of the notification icon based on the type.
	 *
	 * @param string $type The type of notification (success, danger, warning, info).
	 * @return string The URL of the notification icon.
	 */
	private function get_icon_notification_url( $type ) {
		$icon_path = $this->plugin_dir_url . 'view/images/';
		switch ( $type ) {
			case 'success':
				return $icon_path . 'check.svg';
			case 'error':
				return $icon_path . 'alert-triangle.svg';
			case 'warning':
				return $icon_path . 'info.svg';
			case 'info':
				return $icon_path . 'bell.svg';
			default:
				return $icon_path . 'bell.svg';
		}
	}
	/**
	 * Get the HTML for the notification icon based on the type.
	 *
	 * @param string $type The type of notification (success, danger, warning, info).
	 * @return string The HTML string for the notification icon.
	 */
	private function get_icon_notification( $type ) {
		$icon_path = $this->get_icon_notification_url( $type );
		return '<img src="' . $icon_path . '" class="icon" width="16" height="16" alt="icon-' . $type . '">';
	}

	/**
	 * Renders a notification message.
	 *
	 * @param string $msg The notification message to display.
	 * @param string $type The type of notification (e.g., 'info', 'error', 'success'). Defaults to 'info'.
	 * @param bool|string $dismissID The ID used to dismiss the notification. If false, the notification is not dismissible.
	 * @param bool|string $title The title of the notification. If false, the notification is displayed without a title.
	 * @param bool|array $app_notification An array containing 'end_date' and 'id' for app-specific notifications. If false, no app-specific notification is rendered.
	 * @param bool|string $dismissibleId The ID used for dismissing the notification via AJAX. If false, no AJAX dismissible functionality is added.
	 *
	 * @return void
	 */
	public function render_notification( $msg, $type, $dismissID = false, $title = false, $app_notification = false, $dismissibleId = false ) {
		if ( ! $type )
			$type = 'info';
		if ( ! $title )
			$classes = 'compact';
		if ( $dismissID ) {
			if ( ! empty( $_COOKIE[ "dismissed_notice_" . $dismissID ] ) )
				return;
			echo '<div class="nitro-notification notification-' . $type . ' ' . $classes . ' is-dismissible" data-dismissible-id="' . $dismissibleId . '">';
		} else {
			echo '<div class="nitro-notification notification-' . $type . ' ' . $classes . '">';
		} ?>
		<div class="notification-inner">
			<div class="title-msg">
				<div class="title-wrapper">
					<?php if ( $title ) {
						echo $this->get_icon_notification( $type );
						echo $title;
					} ?>
				</div>
				<div class="msg">
					<?php if ( ! $title ) {
						echo $this->get_icon_notification( $type );
					}
					echo $msg;
					?>
				</div>
			</div>
			<div class="col-span-4 ml-auto actions">
				<?php if ( $app_notification ) {
					echo '<a class="btn btn-secondary btn-dismiss rml_btn" data-notification_end="' . $app_notification['end_date'] . '" data-notification_id="' . $app_notification['id'] . '">' . esc_html__( 'Dismiss', 'nitropack' ) . '</a>';
				} else if ( $dismissibleId ) {
					echo '<a class="btn btn-secondary btn-dismiss" onclick="jQuery.post(ajaxurl, {action: \'' . $dismissibleId . '\', nonce: \'' . wp_create_nonce( NITROPACK_NONCE ) . '\'}); jQuery(this).closest(\'.is-dismissible\').hide();">' . esc_html__( 'Dismiss', 'nitropack' ) . '</a>';
				} ?>
			</div>
		</div>
		</div>
	<?php }
	/**
	 * Renders a toggle switch.
	 *
	 * @param string $id The ID attribute for the checkbox input.
	 * @param int $value The value to determine if the checkbox should be checked. If the value is greater than 0, the checkbox will be checked.
	 */
	public function render_toggle( $id, $value ) { ?>
		<label class="inline-flex items-center cursor-pointer ml-auto">
			<input type="checkbox" id="<?php echo $id; ?>" class="sr-only peer" <?php echo (int) $value > 0 ? "checked" : ""; ?>>
			<div class="toggle"></div>
		</label>
		<?php
	}
	/**
	 * Renders a button or link element with specified attributes and options.
	 *
	 * @param array $args {
	 *     Array of arguments for rendering the button or link.
	 *
	 *     @type string $text       The text to display inside the button or link. Default empty.
	 *     @type string $type       The type of element to render, either 'button' or 'link'. Default 'button'.
	 *     @type string $classes    CSS classes to apply to the element. Default 'btn btn-secondary'.
	 *     @type string|null $href  The URL for the link element. Only used if 'type' is 'link'. Default null.
	 *     @type string $icon       The filename of the icon to display inside the element. Default empty.
	 *     @type array $attributes  Additional HTML attributes to apply to the element. Default empty array.
	 * }
	 */
	public function render_button( $args ) {
		$defaults = [ 
			'text' => '',
			'type' => 'button',
			'classes' => 'btn btn-secondary',
			'href' => null,
			'icon' => '',
			'attributes' => []
		];
		$options = wp_parse_args( $args, $defaults );
		$attrs = '';
		foreach ( $options['attributes'] as $key => $value ) {
			$attrs .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}
		if ( $options['type'] === 'button' ) : ?>
			<button <?php else :
			echo '<a href="' . $options['href'] . '"'; ?> 		<?php endif; ?>
			class="<?php echo esc_attr( $options['classes'] ); ?>" <?php echo $attrs; ?>>
			<?php if ( $options['icon'] ) : ?>
				<img src="<?php echo esc_url( $this->plugin_dir_url . 'view/images/' . $options['icon'] ); ?>"
					class="inline-block mr-2" alt="">
			<?php endif; ?>
			<span class="btn-text"><?php echo esc_html( $options['text'] ); ?></span>
			<?php if ( $options['type'] === 'button' ) : ?>
			</button>
		<?php else : ?>
			</a>
		<?php endif; ?>
	<?php
	}
	/**
	 * Renders a tooltip with the specified text and icon.
	 *
	 * @param string $id The unique identifier for the tooltip.
	 * @param string $text The text content to display inside the tooltip.
	 * @param string $icon The filename of the icon to display (default is 'info.svg').
	 */
	public function render_tooltip( $id, $text, $icon = 'info.svg' ) { ?>
		<span class="tooltip-icon" data-tooltip-target="tooltip-<?php echo $id; ?>">
			<img src="<?php echo $this->plugin_dir_url . 'view/images/' . $icon; ?>">
		</span>
		<div id="tooltip-<?php echo $id; ?>" role="tooltip" class="tooltip-container hidden">
			<?php echo wp_kses_post( $text ); ?>
			<div class="tooltip-arrow" data-popper-arrow></div>
		</div>
		<?php
	}
	/**
	 * Renders a fancy radio button component.
	 *
	 * @param string $value   The value attribute for the radio button.
	 * @param string $id      The id attribute for the radio button.
	 * @param string $name    The name attribute for the radio button.
	 * @param bool   $checked Whether the radio button is checked.
	 * @param string $label   The label text for the radio button.
	 * @param string $text    Additional text to display under the label.
	 */
	public function render_fancy_radio( $value, $id, $name, $checked, $label, $text ) { ?>
		<div class="fancy-radio-container <?php echo $checked ? "selected" : ""; ?>" data-value="<?php echo $value; ?>">
			<div class="fancy-radio <?php echo $checked ? "selected" : ""; ?>"><span class="input-fancy-radio"></span></div>
			<label for="<?php echo $id; ?>"><?php echo $label; ?>
				<p><?php echo $text; ?></p>
			</label>
		</div>
		<?php
	}
}
