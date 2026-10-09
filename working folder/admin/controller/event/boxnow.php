<?php
namespace Opencart\Admin\Controller\Extension\Boxnow\Event;
/**
 * Class Boxnow
 *
 * Admin event hooks for the BOX NOW shipping extension. Registered against core
 * triggers at install time (see Controller\Extension\Boxnow\Shipping\Boxnow::install),
 * so the admin menu template never needs to be modified directly.
 *
 * @package Opencart\Admin\Controller\Extension\Boxnow\Event
 */
class Boxnow extends \Opencart\System\Engine\Controller {
	/**
	 * admin/view/common/column_left/before
	 *
	 * Adds the "BOX NOW Delivery" entry to the admin sidebar, linking to the orders report.
	 *
	 * @param string $route
	 * @param array  $data
	 * @param string $code
	 *
	 * @return void
	 */
	public function columnLeft(string &$route, array &$data, string &$code): void {
		if (!isset($this->session->data['user_token']) || !$this->user->hasPermission('access', 'extension/boxnow/shipping/boxnow')) {
			return;
		}

		$this->load->language('extension/boxnow/shipping/boxnow');

		$data['menus'][] = [
			'id'       => 'menu-boxnow',
			'icon'     => 'fa-solid fa-box',
			'name'     => $this->language->get('heading_title'),
			'href'     => $this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token']),
			'children' => []
		];
	}
}
