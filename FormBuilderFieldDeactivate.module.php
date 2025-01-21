<?php namespace ProcessWire;

class FormBuilderFieldDeactivate extends WireData implements Module {

	/**
	 * Ready
	 */
	public function ready() {
		$this->addHookAfter('ProcessFormBuilder::buildEditForm', $this, 'afterBuildEditForm');
		$this->addHookAfter('ProcessFormBuilder::executeEditForm', $this, 'afterExecuteEditForm');
		$this->addHookAfter('ProcessFormBuilder::executeSaveFormSettings', $this, 'afterSaveFormSettings');
		$this->addHookBefore('FormBuilderProcessor::renderOrProcessReady', $this, 'beforeRenderOrProcessReady');
		$this->addHookAfter('FormBuilderProcessor::emailFormPopulateSkipFields', $this, 'afterEmailFormPopulateSkipFields');
	}

	/**
	 * Get an array of deactivated fields in the given form
	 *
	 * @param InputfieldForm $form
	 * @return array
	 */
	public function getDeactivatedFields($form) {
		$deactivated = [];
		foreach($form->getAll(['withWrappers' => true]) as $f) {
			if(!$f->fbfdDeactivate) continue;
			$deactivated[] = $f;
		}
		return $deactivated;
	}

	/**
	 * After ProcessFormBuilder::buildEditForm
	 * Modify the edit form screen
	 *
	 * @param HookEvent $event
	 */
	protected function afterBuildEditForm(HookEvent $event) {
		/** @var ProcessFormBuilder $pfb */
		$pfb = $event->object;
		/** @var InputfieldForm $form */
		$form = $event->return;
		$form_id = $event->arguments(0);
		if(!$form_id) return;
		/** @var FormBuilderForm $fb_form */
		$fb_form = $pfb->forms->load($form_id);

		$form_fields = $form->getChildByName('form_fields');
		if(!$form_fields) return;

		// Add inputfield to deactivate fields
		/** @var InputfieldCheckboxes $f */
		$f = $this->wire()->modules->get('InputfieldCheckboxes');
		$f_name = 'deactivatedFields';
		$f->name = $f_name;
		$f->label = $this->_('Deactivated fields');
		$f->icon = 'toggle-off';
		$f->optionWidth = '380px';
		$deactivated = [];
		foreach($fb_form->getChildrenFlat() as $fb_field) {
			// Skip if the field doesn't have a type (applies to fieldset_end)
			if(!$fb_field->type) continue;
			if($fb_field->fbfdDeactivate) $deactivated[$fb_field->name] = $fb_field->name;
			$f->addOption($fb_field->name);
		}
		$f->value = $deactivated;
		if(!$deactivated) $f->collapsed = Inputfield::collapsedYes;
		$form->insertBefore($f, 'submit_save_form');

		// Modify form_fields AsmSelect so deactivate fields can receive special styling
		foreach($form_fields->options as $value => $label) {
			if(!isset($deactivated[$value])) continue;
			$attr = $form_fields->getOptionAttributes($value);
			$desc = "<span class='fbfd-deactivated'>{$attr['data-desc']}</span>";
			$form_fields->addOptionAttributes($value, ['data-desc' => $desc]);
		}
	}

	/**
	 * After ProcessFormBuilder::executeEditForm
	 * Add assets here so that JS will be loaded after AsmSelect JS
	 *
	 * @param HookEvent $event
	 */
	protected function afterExecuteEditForm(HookEvent $event) {
		$config = $this->wire()->config;
		$info = $this->wire()->modules->getModuleInfo($this);
		$version = $info['version'];
		$config->scripts->add($config->urls->$this . "fbfd-edit-form.js?v=$version");
		$config->styles->add($config->urls->$this . "fbfd-edit-form.css?v=$version");
		$config->js($this->className, [
			'fbfd_label' => $this->_(' [deactivated]')
		]);
	}

	/**
	 * After ProcessFormBuilder::executeSaveFormSettings
	 * Update field settings when edit form is saved
	 *
	 * @param HookEvent $event
	 */
	protected function afterSaveFormSettings(HookEvent $event) {
		/** @var FormBuilderForm $fb_form */
		$fb_form = $event->arguments(0);
		$deactivated = $this->wire()->input->post('deactivatedFields');
		if(!$deactivated) $deactivated = [];
		$fb_fields = $fb_form->getChildrenFlat();
		foreach($fb_fields as $name => $fb_field) {
			$fb_field->fbfdDeactivate = in_array($name, $deactivated) ? 1 : 0;
		}
	}

	/**
	 * Before FormBuilderProcessor::renderOrProcessReady
	 * Remove deactivated fields from front-end form, and add a deactivated notice in the ProcessWire admin
	 *
	 * @param HookEvent $event
	 */
	protected function beforeRenderOrProcessReady(HookEvent $event) {
		/** @var InputfieldForm $form */
		$form = $event->arguments(0);
		$is_admin = $this->wire()->config->admin;
		$notice = $this->_(' [currently deactivated]');
		$deactivated = $this->getDeactivatedFields($form);
		foreach($deactivated as $f) {
			if($is_admin) {
				$f->label .= $notice;
			} else {
				$f->collapsed = Inputfield::collapsedHidden;
			}
		}
	}

	/**
	 * FormBuilderProcessor::emailFormPopulateSkipFields
	 * Exclude deactivated fields from email
	 *
	 * @param HookEvent $event
	 */
	protected function afterEmailFormPopulateSkipFields(HookEvent $event) {
		/** @var FormBuilderEmail $email */
		$email = $event->arguments(0);
		/** @var InputfieldForm $form */
		$form = $event->arguments(1);
		$deactivated = $this->getDeactivatedFields($form);
		foreach($deactivated as $f) {
			$email->setSkipFieldName($f->name);
		}
	}

}
