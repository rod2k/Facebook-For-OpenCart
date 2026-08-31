<?php
/**
  * Copyright (c) Facebook, Inc. and its affiliates.
  * All rights reserved.
  *
  * This source code is licensed under the license found in the
  * LICENSE file in the root directory of this source tree.
  */
class ControllerExtensionModuleFacebookBusiness extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/facebook_business');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('extension/module/facebook_business');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_facebook_business', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module/facebook_business', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/facebook_business', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/facebook_business', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_facebook_business_status'])) {
			$data['module_facebook_business_status'] = $this->request->post['module_facebook_business_status'];
		} else {
			$data['module_facebook_business_status'] = $this->config->get('module_facebook_business_status');
		}

		if (isset($this->request->post['module_facebook_business_cookie_bar_status'])) {
			$data['module_facebook_business_cookie_bar_status'] = $this->request->post['module_facebook_business_cookie_bar_status'];
		} else {
			$data['module_facebook_business_cookie_bar_status'] = $this->config->get('module_facebook_business_cookie_bar_status');
		}

		if (isset($this->request->post['module_facebook_business_sync_specials_status'])) {
			$data['module_facebook_business_sync_specials_status'] = $this->request->post['module_facebook_business_sync_specials_status'];
		} else {
			$data['module_facebook_business_sync_specials_status'] = $this->config->get('module_facebook_business_sync_specials_status');
		}

		if (isset($this->request->post['module_facebook_business_store_code'])) {
			$data['module_facebook_business_store_code'] = $this->request->post['module_facebook_business_store_code'];
		} else {
			$data['module_facebook_business_store_code'] = $this->config->get('module_facebook_business_store_code');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/facebook_business', $data));
	}

	public function updateSettings() {
		if (!$this->validate()) {
			$this->session->data['error'] = $this->error['warning'];
			return $this->response->redirect($this->url->link('extension/module/facebook_business', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->model('setting/setting');
		$this->load->model('extension/module/facebook_business');

		$settings = array(
			'module_facebook_business_pixel_id'                   => '',
			'module_facebook_business_page_id'                    => '',
			'module_facebook_business_fbe_v2_installed'           => '',
			'module_facebook_business_system_user_access_token'   => '',
			'module_facebook_business_jssdk_version'              => '',
			'module_facebook_business_messenger_activated'        => ''
		);

		foreach ($settings as $key => &$value) {
			if (isset($this->request->post[$key])) {
				$value = $this->request->post[$key];
			}
		}

		$this->model_setting_setting->editSetting('module_facebook_business', $settings);

		$json = array();
		$json['success'] = true;
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function deleteSettings() {
		$json = array();
		$json['success'] = true;
		if ($this->validate()) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->deleteSetting('module_facebook_business');
			$json['success'] = true;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/facebook_business')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
