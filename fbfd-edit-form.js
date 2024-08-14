$(document).ready(function() {

	const js_config = ProcessWire.config.FormBuilderFieldDeactivate;
	const $fields_asm = $('#wrap_form_fields');

	// Apply deactivated styling to fields within deactivated fieldsets
	function processFieldsets() {
		$fields_asm.find('.fbfd-deactivated-child').removeClass('fbfd-deactivated-child');
		const $deactivated_fieldsets = $fields_asm.find('.asmFieldset.fbfd-deactivated-item');
		$deactivated_fieldsets.each(function() {
			const name = $(this).find('.asmListItemLabel a').text();
			const $fs_end = $fields_asm.find(`.asmFieldsetEnd .asmListItemLabel a:contains(${name}_END)`).closest('.asmFieldsetEnd');
			if($fs_end.length) {
				$fs_end.addClass('fbfd-deactivated-item')
				$(this).nextUntil($fs_end).addClass('fbfd-deactivated-child');
			}
		});
	}

	// Apply deactivated styling to deactivated fields
	$fields_asm.find('.fbfd-deactivated').each(function() {
		$(this).closest('.asmListItem').addClass('fbfd-deactivated-item').find('.asmListItemLabel').attr('data-fbfd-label', js_config.fbfd_label);
	});

	// Process fieldsets on DOM ready
	processFieldsets();

	// Process fieldsets after AsmSelect is sorted
	$(document).on('sorted', function() {
		processFieldsets();
	});

});
