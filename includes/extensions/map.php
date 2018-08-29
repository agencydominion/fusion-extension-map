<?php
/**
 * @package Fusion_Extension_Map
 */

/**
 * Google Maps Extension.
 *
 * Function for adding a map element to the Fusion Engine
 *
 * @since 1.0.0
 */

//Google Map

class FusionMap	{

	public function __construct() {

		//add map shortcode
		add_shortcode('fsn_map', array($this, 'map_shortcode'));

		//load map layout via AJAX
		add_action('wp_ajax_map_load_layout', array($this, 'load_map_layout'));

		//load saved map layout fields
		add_filter('fsn_element_params', array($this, 'load_saved_map_layout_fields'), 10, 3);

		//initialize map
		add_action('init', array($this, 'fsn_init_map'), 12);

		//add basic map layout
		add_filter('add_map_layout', array($this, 'google_map_embed'));

		//add advanced map layout
		add_filter('add_map_layout', array($this, 'google_map_custom'));

	}
	/**
	 * Load Map Layout
	 *
	 * @since 1.0.0
	 */

	 public function load_map_layout() {
		//verify nonce
		check_ajax_referer( 'fsn-admin-edit-map', 'security' );

		//verify capabilities
		if ( !current_user_can( 'edit_post', intval($_POST['post_id']) ) )
			die( '-1' );

		global $fsn_map_layouts;
		$map_layout = sanitize_text_field($_POST['map_layout']);
		$response_array = array();

		if (!empty($fsn_map_layouts) && !empty($map_layout)) {
			$response_array = array();
			foreach($fsn_map_layouts[$map_layout]['params'] as $param) {
				$param_value = '';
				$param['section'] = !empty($param['section']) ? $param['section'] : 'general';
				//check for dependency
				$dependency = !empty($param['dependency']) ? true : false;
				if ($dependency === true) {
					$depends_on_field = $param['dependency']['param_name'];
					$depends_on_not_empty = !empty($param['dependency']['not_empty']) ? $param['dependency']['not_empty'] : false;
					if (!empty($param['dependency']['value']) && is_array($param['dependency']['value'])) {
						$depends_on_value = json_encode($param['dependency']['value']);
					} else if (!empty($param['dependency']['value'])) {
						$depends_on_value = $param['dependency']['value'];
					} else {
						$depends_on_value = '';
					}
					$dependency_callback = !empty($param['dependency']['callback']) ? $param['dependency']['callback'] : '';
					$dependency_string = ' data-dependency-param="'. esc_attr($depends_on_field) .'"'. ($depends_on_not_empty === true ? ' data-dependency-not-empty="true"' : '') . (!empty($depends_on_value) ? ' data-dependency-value="'. esc_attr($depends_on_value) .'"' : '') . (!empty($dependency_callback) ? ' data-dependency-callback="'. esc_attr($dependency_callback) .'"' : '');
				}
				$param_output = '<div class="form-group map-layout'. ( !empty($param['class']) ? ' '. esc_attr($param['class']) : '' ) .'"'. ( $dependency === true ? $dependency_string : '' ) .'>';
					$param_output .= FusionCore::get_input_field($param, $param_value);
				$param_output .= '</div>';
				$response_array[] = array(
					'section' => $param['section'],
					'output' => $param_output
				);
			}
		}

		header('Content-type: application/json');

		echo json_encode($response_array);

		exit;
	}

	/**
	 * Load Saved Map Layout Fields
	 *
	 * @since 1.0.0
	 */

	public function load_saved_map_layout_fields($params, $shortcode, $saved_values) {

		global $fsn_map_layouts;

		if ($shortcode == 'fsn_map' && !empty($saved_values['map-layout']) && array_key_exists($saved_values['map-layout'], $fsn_map_layouts)) {
			$saved_layout = $saved_values['map-layout'];
			$params_to_add = !empty($fsn_map_layouts[$saved_layout]['params']) ? $fsn_map_layouts[$saved_layout]['params'] : '';
			if (!empty($params_to_add)) {
				for ($i=0; $i < count($params_to_add); $i++) {
					if (empty($params_to_add[$i]['class'])) {
						$params_to_add[$i]['class'] = 'map-layout';
					} else {
						$params_to_add[$i]['class'] .= ' map-layout';
					}
				}
			}
			//add layout params to initial load
			array_splice($params, 1, 0, $params_to_add);
		}

		return $params;
	}

	/**
	 * Initialize map
	 *
	 * @since 1.0.0
	 */


	public function fsn_init_map() {

		//MAP SHORTCODE
		if (function_exists('fsn_map')) {

			//define map layouts
			$map_layouts = array();

			//get layouts
			$map_layouts = apply_filters('add_map_layout', $map_layouts);

			//create map layouts global
			global $fsn_map_layouts;
			$fsn_map_layouts = $map_layouts;

			//pass layouts array to script
			wp_localize_script('fsn_google_map', 'fsnMap', $map_layouts);

			//get map layout options
			if (!empty($map_layouts)) {
				$map_layout_options = array();
				$smart_supported = array();
				$layout_specific_params = array();
				$map_layout_options[''] = __('Choose map type.', 'fusion-extension-map');
				foreach($map_layouts as $key => $value) {
					//create array of layouts for select layout dropdown
					$map_layout_options[$key] = $value['name'];
				}
				//add layout list items to global
				foreach($map_layouts as $map_layout) {
					foreach($map_layout['params'] as $map_layout_param) {
						if ($map_layout_param['type'] == 'custom_list') {
							global $fsn_custom_lists;
							$fsn_custom_lists[$map_layout_param['id']]['parent'] = 'fsn_map';
							$fsn_custom_lists[$map_layout_param['id']]['params'] = $map_layout_param['item_params'];
						}
					}
				}
			}

			$params_array = array(
				array(
					'type' => 'select',
					'options' => $map_layout_options,
					'param_name' => 'map_layout',
					'label' => __('Type', 'fusion-extension-map'),
				)
			);

			fsn_map(array(
				'name' => __('Map', 'fusion-extension-map'),
				'description' => __('Add a Map. Choose the map type to see additional configuration options.', 'fusion-extension-map'),
				'shortcode_tag' => 'fsn_map',
				'icon' => 'place',
				'disable_style_params' => array('text_align','text_align_xs','font_size','color'),
				'params' => $params_array
			));
		}
	}

	/**
	 * Map shortcode
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 */

	public function map_shortcode( $atts, $content ) {
		extract( shortcode_atts( array(
			'map_layout' => false
		), $atts ) );

		$output = '';

		if (!empty($map_layout)) {
			$output .= '<div class="fsn-map '. esc_attr($map_layout) .' '. fsn_style_params_class($atts) .'">';
				$callback_function = 'fsn_get_'. sanitize_text_field($map_layout) .'_map';
				$output .= call_user_func($callback_function, $atts, $content);
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Google Map Embed layout
	 */

	public function google_map_embed($map_layouts) {

		//basic map layout
		$google_map_embed = array(
			'name' => __('Google Map Embed', 'fusion-extension-map'),
			'params' => array(
				array(
					'type' => 'textarea',
					'param_name' => 'map_embed_code',
					'content_field' => true,
					'encode_base64' => true,
					'label' => __('Google Map Embed Code', 'fusion-extension-map'),
					'help' => __('To get the embed code, click "Share" on any Google Map, choose "Embed map" and copy the embed code.', 'fusion-extension-map')
				),
				array(
					'type' => 'text',
					'param_name' => 'map_height',
					'label' => __('Map Height', 'fusion-extension-map'),
					'help' => __( 'Default is 300px.', 'fusion-extension-map'),
					'section' => 'style'
				)
			)
		);
		$map_layouts['google_map_embed'] = $google_map_embed;

		return $map_layouts;
	}

	/**
	 * Google Map Custom layout
	 */

	 public function google_map_custom($map_layouts)	{

		//advanced map layout

		//Zoom Level options
		$zoom_arr = array(
			'14' => __('14', 'fusion-extension-map'),
			'1' => __('1', 'fusion-extension-map'),
			'2' => __('2', 'fusion-extension-map'),
			'3' => __('3', 'fusion-extension-map'),
			'4' => __('4', 'fusion-extension-map'),
			'5' => __('5', 'fusion-extension-map'),
			'6' => __('6', 'fusion-extension-map'),
			'7' => __('7', 'fusion-extension-map'),
			'8'=> __('8', 'fusion-extension-map'),
			'9' => __('9', 'fusion-extension-map'),
			'10' => __('10', 'fusion-extension-map'),
			'11' => __('11', 'fusion-extension-map'),
			'12' => __('12', 'fusion-extension-map'),
			'13' => __('13', 'fusion-extension-map'),
			'15' => __('15', 'fusion-extension-map'),
			'16' => __('16', 'fusion-extension-map'),
			'17' => __('17', 'fusion-extension-map'),
			'18' => __('18', 'fusion-extension-map'),
			'19' => __('19', 'fusion-extension-map'),
			'20' => __('20', 'fusion-extension-map')
		);

		//Map Control Position
		$controlpos_arr = array(
			'google.maps.ControlPosition.TOP_LEFT' => __('Top Left', 'fusion-extension-map'),
			'google.maps.ControlPosition.TOP_CENTER' => __('Top Center', 'fusion-extension-map'),
			'google.maps.ControlPosition.TOP_RIGHT' => __('Top Right', 'fusion-extension-map'),
			'google.maps.ControlPosition.LEFT_TOP' => __('Left Top', 'fusion-extension-map'),
			'google.maps.ControlPosition.RIGHT_TOP' => __('Right Top', 'fusion-extension-map'),
			'google.maps.ControlPosition.LEFT_CENTER' => __('Left Center', 'fusion-extension-map'),
			'google.maps.ControlPosition.RIGHT_CENTER' => __('Right Center', 'fusion-extension-map'),
			'google.maps.ControlPosition.LEFT_BOTTOM' => __('Left Bottom', 'fusion-extension-map'),
			'google.maps.ControlPosition.RIGHT_BOTTOM' => __('Right Bottom', 'fusion-extension-map'),
			'google.maps.ControlPosition.BOTTOM_CENTER' => __('Bottom Center', 'fusion-extension-map'),
			'google.maps.ControlPosition.BOTTOM_LEFT' => __('Bottom Left', 'fusion-extension-map'),
			'google.maps.ControlPosition.BOTTOM_RIGHT' => __('Bottom Right', 'fusion-extension-map')
		);

		//Map Type options
		$type_arr = array(
			'ROADMAP' => __('Road Map', 'fusion-extension-map'),
			'SATELLITE' => __('Satellite', 'fusion-extension-map'),
			'HYBRID' => __('Hybrid', 'fusion-extension-map'),
			'TERRAIN' => __('Terrain', 'fusion-extension-map')
		);

		//Map Type Control options
		$mapTypeControl_arr = array(
			'DEFAULT' => __('Default', 'fusion-extension-map'),
			'HORIZONTAL_BAR' => __('Horizontal Bar', 'fusion-extension-map'),
			'DROPDOWN_MENU' => __('Dropdown Menu', 'fusion-extension-map')
		);

		$google_map_custom = array(
			'name' => __('Google Map Custom', 'fusion-extension-map'),
			'params' => array(
				array(
					'type' => 'text',
					'param_name' => 'lat_long',
					'label' => __('Map Center Coordinates', 'fusion-extension-map'),
					'help' => __( 'Input the latitude and longitude coordinates the map should center on.', 'fusion-extension-map')
				),
				array(
					'type' => 'custom_list',
					'param_name' => 'custom_list_items',
					'id' => 'google_map_marker', //each custom list requires a unique ID
					'item_params' => array(
						array(
							'type' => 'image',
							'param_name' => 'image_id',
							'label' => __('Marker Image', 'fusion-extension-map')
						),
						array(
							'type' => 'select',
							'options' => array(
								'bottom_center' => __('Bottom Center', 'fusion-extension-map'),
								'center' => __('Center', 'fusion-extension-map')
							),
							'param_name' => 'marker_anchor_point_position',
							'label' => __('Anchor Point Position', 'fusion-extension-map'),
							'help' => __('Choose anchor point position from the custom marker image.', 'fusion-extension-map'),
						),
						array(
							'type' => 'text',
							'param_name' => 'marker_co',
							'label' => __('Map Marker Coordinates', 'fusion-extension-map'),
							'help' => __( 'To find the coordinates, right-click on your desired location within Google Maps and click "What\'s here?". The box at the bottom will contain the coordinates.', 'fusion-extension-map')
						),
						array(
							'type' => 'textarea',
							'param_name' => 'aux_content',
							'label' => __('Popup Content', 'fusion-extension-map'),
							'help' => __('Input map marker tooltip text.', 'fusion-extension-map')
						),
						array(
							'type' => 'checkbox',
							'param_name' => 'infobox_open',
							'label' => __('Show Popup on Load.', 'fusion-extension-map')
						)
					),
					'label' => __('Map Markers', 'fusion-extension-map'),
					'help' => __('Drag-and-drop blocks to re-order.', 'fusion-extension-map'),
				),
				array(
					'type' => 'select',
					'options' => $type_arr,
					'param_name' => 'map_type',
					'label' => __('Map Type', 'fusion-extension-map'),
					'section' => 'advanced'
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'type_control',
					'label' => __('Map Type Control', 'fusion-extension-map'),
					'section' => 'advanced'
				),
				array(
					'type' => 'select',
					'options' => $mapTypeControl_arr,
					'param_name' => 'typecontrol_style',
					'label' => __('Map Type Control Style', 'fusion-extension-map'),
					'section' => 'advanced',
					'dependency' => array(
						'param_name' => 'type_control',
						'not_empty' => true
					)
				),
				array(
					'type' => 'select',
					'options' => $controlpos_arr,
					'param_name' => 'type_pos',
					'label' => __('Map Type Control Position', 'fusion-extension-map'),
					'section' => 'advanced',
					'dependency' => array(
						'param_name' => 'type_control',
						'not_empty' => true
					)
				),
				array(
					'type' => 'select',
					'options' => $zoom_arr,
					'param_name' => 'zoom_level',
					'label' => __('Zoom', 'fusion-extension-map'),
					'section' => 'advanced'
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'zoom_control',
					'label' => __('Zoom Control', 'fusion-extension-map'),
					'section' => 'advanced'
				),
				array(
					'type' => 'select',
					'options' => $controlpos_arr,
					'param_name' => 'zoom_pos',
					'label' => __('Zoom Control Position', 'fusion-extension-map'),
					'section' => 'advanced',
					'dependency' => array(
						'param_name' => 'zoom_control',
						'not_empty' => true
					)
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'scale_control',
					'label' => __('Scale Control', 'fusion-extension-map'),
					'section' => 'advanced'
				),
				array(
					'type' => 'text',
					'param_name' => 'map_height',
					'label' => __('Map Height', 'fusion-extension-map'),
					'help' => __( 'Default is 300px.', 'fusion-extension-map'),
					'section' => 'style'
				),
				array(
					'type' => 'textarea',
					'param_name' => 'map_styles',
					'label' => __('Map Styles', 'fusion-extension-map'),
					'help' => __('Input a custom styles array (e.g. from Snazzy Maps) to change the map appearance.', 'fusion-extension-map'),
					'section' => 'style'
				)
			)
		);
		$map_layouts['google_map_custom'] = $google_map_custom;

		return $map_layouts;
	}
}

$fsn_map = new FusionMap();

//Basic Map

//render map layout ** function name must follow fsn_get_[map layout key]_map
function fsn_get_google_map_embed_map($atts = false, $content = false) {
	extract( shortcode_atts( array(
		'map_height' => '300px'
	), $atts ) );

	$output = '<div class="fsn-googlemap_container" style="height:' . esc_attr($map_height) . ';">';
		$output .= !empty($content) ? base64_decode( wp_strip_all_tags($content) ) : '';
	$output .= '</div>';

	return $output;
}

//Advanced Map

//render map layout ** function name must follow fsn_get_[map layout key]_map
function fsn_get_google_map_custom_map($atts = false, $content = false) {
	extract(shortcode_atts(array(
	    'zoom_level' => '14',
		'lat_long' => '0,0',
		'map_height' => '300px',
		'zoom_control' => '',
		'zoom_pos' => 'google.maps.ControlPosition.TOP_LEFT',
		'type_control' => '',
		'map_type' => 'ROADMAP',
		'typecontrol_style' => 'DEFAULT',
		'type_pos' => 'google.maps.ControlPosition.TOP_RIGHT',
		'scale_control' => '',
		'map_styles' => '[]'
	), $atts));

	/**
	 * Enqueue Scripts
	 */

	add_action('wp_footer', 'fsn_google_maps_api_script', 10);

	$id = uniqid();

	//plugin
	wp_enqueue_script('fsn_map');

	$zoom_control = !empty($zoom_control) ? 'true' : 'false';
	$type_control = !empty($type_control) ? 'true' : 'false';
	$scale_control = !empty($scale_control) ? 'true' : 'false';


	$output = '<div class="fsn-googlemap_container_'.esc_attr($id).'" id="fsn_googlemap_'.esc_attr($id).'" style="width:100%;height:' . esc_attr($map_height) . ';"></div>';

	ob_start();
	?>
	<script type="text/javascript">
		jQuery(window).on('load', function(){
			if (typeof google === 'object' && typeof google.maps === 'object') {
				var places = [];
				<?php echo do_shortcode($content); ?>
				fsn_google_maps_init(<?php echo esc_attr($lat_long); ?>,'fsn_googlemap_<?php echo esc_attr($id); ?>',places,<?php echo esc_attr($zoom_level); ?>,'<?php echo esc_attr($map_type); ?>',<?php echo esc_attr($zoom_control); ?>,<?php echo esc_attr($zoom_pos); ?>,<?php echo esc_attr($type_control); ?>,'<?php echo esc_attr($typecontrol_style); ?>',<?php echo esc_attr($type_pos); ?>,'<?php echo esc_attr($map_styles); ?>',<?php echo esc_attr($scale_control); ?>);
			}
		});
	</script>
	<?php
	$output .= ob_get_clean();

	return $output;
}
//render list item ** function name must follow fsn_get_[list_id]_list_item
function fsn_get_google_map_marker_list_item($atts = false, $content = false) {
	$output = '';

	$id = uniqid();

	$marker_latlng = explode( ',', $atts['marker_co'] );
	$popup_content = !empty($atts['aux_content']) ? $atts['aux_content'] : '';
	$popup_content = nl2br($popup_content);
	$popup_open = !empty($atts['infobox_open']) ? 'true' : 'false';
	$breaks = array("\r\n", "\n", "\r");
	$popup_content_no_breaks = str_replace($breaks, "", $popup_content);

	if (!empty($atts['image_id'])) {
		$attachment = get_post($atts['image_id']);
		$attachment_attrs = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' );
		$marker_anchor_position = !empty($atts['marker_anchor_point_position']) ? $atts['marker_anchor_point_position'] : 'bottom_center';
		$output .= "var place_".esc_attr($id)."= { marker : { position:{ lat:".esc_attr($marker_latlng[0]).", lng:".esc_attr($marker_latlng[1])." }, icon: { url:'".(!empty($attachment_attrs) ? esc_attr($attachment_attrs[0]) : '')."', width: '".(!empty($attachment_attrs) ? esc_attr($attachment_attrs[1]) : '')."', height: '".(!empty($attachment_attrs) ? esc_attr($attachment_attrs[2]) : '')."', anchorPosition: '". esc_attr($marker_anchor_position) ."' } }, infoWindow: { content:'".esc_attr($popup_content_no_breaks)."', open:'". esc_attr($popup_open) ."' } }; places.push(place_".esc_attr($id)."); ";
	} else {
		$output .= "var place_".esc_attr($id)."= { marker : { position:{ lat:".esc_attr($marker_latlng[0]).", lng:".esc_attr($marker_latlng[1])." }, }, infoWindow: { content:'".esc_attr($popup_content_no_breaks)."', open:'". esc_attr($popup_open) ."' } }; places.push(place_".esc_attr($id)."); ";
	}

	return $output;
}

function fsn_google_maps_api_script(){
	$fsn_google_maps_api_key = FusionExtensionMap::fsn_get_google_maps_api_key();
	?>
	<script src='https://maps.googleapis.com/maps/api/js<?php echo !empty($fsn_google_maps_api_key) ? '?key=' . esc_attr($fsn_google_maps_api_key) : ''; ?>' async></script>
	<?php
}
?>
