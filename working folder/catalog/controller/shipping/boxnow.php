<?php
namespace Opencart\Catalog\Controller\Extension\Boxnow\Shipping;
/**
 * Class Boxnow
 *
 * @package Opencart\Catalog\Controller\Extension\Boxnow\Shipping
 */
class Boxnow extends \Opencart\System\Engine\Controller {
	/**
	 * Stores the locker chosen in the BOX NOW map widget in the session, so it can be
	 * picked up when the order is placed (see Event\Boxnow::addOrder).
	 *
	 * @return void
	 */
	public function setLockerSession(): void {
		$json = [];

		if (isset($this->request->post['locker_id']) && $this->request->post['locker_id']) {
			$this->session->data['boxnow_locker_id'] = (int)$this->request->post['locker_id'];
			$this->session->data['boxnow_address']   = isset($this->request->post['locker_address']) ? (string)$this->request->post['locker_address'] : '';
			$this->session->data['boxnow_name']      = isset($this->request->post['locker_name']) ? (string)$this->request->post['locker_name'] : '';

			$json['success'] = true;
		} else {
			$json['error'] = true;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
