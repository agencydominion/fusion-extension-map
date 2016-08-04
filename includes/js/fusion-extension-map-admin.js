/**
 * WP Admin scripts for map extension
 */

//init map
jQuery(document).ready(function() {
	jQuery('body').on('show.bs.modal', '#fsn_map_modal', function(e) {
		var map = jQuery('#fsn_map_modal');
		var selectLayoutElement = jQuery('[name="map_layout"]');
		var selectedLayout = selectLayoutElement.val();
		
		map.attr('data-layout', selectedLayout);
	});
});

//update map function
jQuery(document).ready(function() {
	jQuery('body').on('change', 'select[name="map_layout"]', function(e) {
		fsnUpdatemap(e);
	});
});

function fsnUpdatemap(event) {
	var selectLayoutElement = jQuery(event.target);		
	var selectedLayout = selectLayoutElement.val();
	var map = jQuery('#fsn_map_modal');
	var currentLayout = map.attr('data-layout');
	if (currentLayout != '' && currentLayout != selectedLayout) {
		var r = confirm('Changing the map Layout will erase your current map. Do you wish to continue?');
		if (r == true) {			
			map.attr('data-layout', selectedLayout);
			fsnUpdateMapLayout();
		} else {
			selectLayoutElement.find('option[value="'+ currentLayout +'"]').prop('selected', true);
		}
	} else {
		map.attr('data-layout', selectedLayout);
		fsnUpdateMapLayout();
	}
}

//update map layout
function fsnUpdateMapLayout() {
	
	var mapLayout = jQuery('[name="map_layout"]').val();
	
	var data = {
		action: 'map_load_layout',
		map_layout: mapLayout
	};
	jQuery.post(ajaxurl, data, function(response) {	
		jQuery('#fsn_map_modal .tab-pane .form-group.map-layout').remove();
		if (response !== null) {
			jQuery('#fsn_map_modal .tab-pane').each(function() {
				var tabPane = jQuery(this);
				if (tabPane.attr('data-section-id') == 'general') {
					tabPane.find('.form-group').first().after('<div class="layout-fields"></div>');
				} else {
					tabPane.prepend('<div class="layout-fields"></div>');
				}
			});
			for(i=0; i < response.length; i++) {
				jQuery('#fsn_map_modal .tab-pane[data-section-id="'+ response[i].section +'"] .layout-fields').append(response[i].output);
			}
			jQuery('#fsn_map_modal .tab-pane').each(function() {
				var tabPane = jQuery(this);
				tabPane.find('.map-layout').first().unwrap();
				tabPane.find('.layout-fields:empty').remove();
			});
		}
		
		//reinit tinyMCE
		var modalSelector = jQuery('#fsn_map_modal');
		if (jQuery('#fsncontent').length > 0) {
			var $element = jQuery('#fsncontent');
	        var qt, textfield_id = $element.attr("id"),
	            content = '';
	
	        window.tinyMCEPreInit.mceInit[textfield_id] = _.extend({}, tinyMCEPreInit.mceInit['content']);
	
	        if(_.isUndefined(tinyMCEPreInit.qtInit[textfield_id])) {
	            window.tinyMCEPreInit.qtInit[textfield_id] = _.extend({}, tinyMCEPreInit.qtInit['replycontent'], {id: textfield_id})
	        }
	        //$element.val($content_holder.val());
	        qt = quicktags( window.tinyMCEPreInit.qtInit[textfield_id] );
	        QTags._buttonsInit();
	        //make compatable with TinyMCE 4 which is used starting with WordPress 3.9
	        if(tinymce.majorVersion === "4") tinymce.execCommand( 'mceAddEditor', true, textfield_id );
	        window.switchEditors.go(textfield_id, 'tmce');
	        //focus on this RTE
	        tinyMCE.get('fsncontent').focus();
			//destroy tinyMCE
			modalSelector.on('hidden.bs.modal', function() {					
				//make compatable with TinyMCE 4 which is used starting with WordPress 3.9
				if(tinymce.majorVersion === "4") {
					tinymce.execCommand('mceRemoveEditor', true, 'fsncontent');
                } else {
					tinymce.execCommand("mceRemoveControl", true, 'fsncontent');
                }
			});
		}
		//set dependencies
		setDependencies(modalSelector);
		//trigger item added event
		jQuery('body').trigger('fsnMapUpdated');
	});	
}

//For chosen fields inside map items
jQuery(document).ready(function() {	
	jQuery('body').on('fsnMapUpdated', function(e) {
		jQuery('.chosen select').chosen({
			allow_single_deselect: true,
			width: '100%',
			placeholder_text_single : 'Choose an option.'
		});
	});
});