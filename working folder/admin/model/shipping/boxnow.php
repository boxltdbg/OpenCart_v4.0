<?php
namespace Opencart\Admin\Model\Extension\Boxnow\Shipping;
/**
 * Class Boxnow
 *
 * @package Opencart\Admin\Model\Extension\Boxnow\Shipping
 */
class Boxnow extends \Opencart\System\Engine\Model {
	/**
	 * @return void
	 */
	public function install(): void {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "boxnow_requests` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `order_id` int(11) NOT NULL,
			  `request_id` int(11) NOT NULL DEFAULT '0',
			  `parcels` text NOT NULL,
			  `locker_id` int(11) NOT NULL,
			  `status_message` text DEFAULT NULL,
			  `status` int(11) NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `order_id` (`order_id`),
			  KEY `request_id` (`request_id`),
			  KEY `status` (`status`),
			  KEY `locker_id` (`locker_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
		");
	}

	/**
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "boxnow_requests`");
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function getBoxNowOrders(array $data = []): array {
		$sql = "SELECT `o`.`order_id`, CONCAT(`o`.`firstname`, ' ', `o`.`lastname`) AS `customer`, (SELECT `os`.`name` FROM `" . DB_PREFIX . "order_status` `os` WHERE `os`.`order_status_id` = `o`.`order_status_id` AND `os`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') AS `order_status`, `o`.`shipping_method`, `o`.`telephone`, `o`.`total`, `o`.`currency_code`, `o`.`currency_value`, `o`.`date_added`, `o`.`date_modified` FROM `" . DB_PREFIX . "order` `o` WHERE `o`.`order_status_id` > '0' AND `o`.`shipping_method` LIKE '%\"code\":\"boxnow.boxnow\"%' ORDER BY `o`.`order_id` DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * @param array $data
	 *
	 * @return int
	 */
	public function getBoxNowTotalOrders(array $data = []): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "order` WHERE `order_status_id` > '0' AND `shipping_method` LIKE '%\"code\":\"boxnow.boxnow\"%'");

		return (int)$query->row['total'];
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function getBoxNowStatus(int $order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "boxnow_requests` WHERE `order_id` = '" . (int)$order_id . "'");

		return $query->row;
	}

	/**
	 * @param int $order_id
	 *
	 * @return float
	 */
	public function getOrderWeight(int $order_id): float {
		$query = $this->db->query("SELECT SUM(`p`.`weight` * `op`.`quantity`) AS `weight` FROM `" . DB_PREFIX . "order_product` `op` LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`op`.`product_id` = `p`.`product_id`) WHERE `op`.`order_id` = '" . (int)$order_id . "'");

		return $query->row['weight'] ? (float)$query->row['weight'] : 1.0;
	}

	/**
	 * Persists the locker chosen in the admin report against an order, whether or
	 * not a boxnow_requests row already exists for it (e.g. because the storefront
	 * checkout never recorded one). Called before every waybill creation attempt so
	 * the locker picked via the map/typed manually is never lost, even if the API
	 * call itself then fails.
	 *
	 * @param int $order_id
	 * @param int $locker_id
	 *
	 * @return void
	 */
	public function saveLockerId(int $order_id, int $locker_id): void {
		if (!$order_id) {
			return;
		}

		if ($this->getBoxNowStatus($order_id)) {
			$this->db->query("UPDATE `" . DB_PREFIX . "boxnow_requests` SET `locker_id` = '" . (int)$locker_id . "' WHERE `order_id` = '" . (int)$order_id . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "boxnow_requests` SET `order_id` = '" . (int)$order_id . "', `request_id` = '0', `parcels` = '[]', `locker_id` = '" . (int)$locker_id . "', `status_message` = NULL, `status` = '2'");
		}
	}

	/**
	 * Stores the customer's phone on the order — the OC4 checkout can create orders
	 * without one, and BOX NOW cannot accept a delivery request without it.
	 *
	 * @param int    $order_id
	 * @param string $telephone
	 *
	 * @return void
	 */
	public function saveOrderTelephone(int $order_id, string $telephone): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET `telephone` = '" . $this->db->escape($telephone) . "' WHERE `order_id` = '" . (int)$order_id . "'");
	}

	/**
	 * @param array $order
	 * @param array $request
	 *
	 * @return void
	 */
	public function updateRequest(array $order, array $request): void {
		if ($order) {
			$this->db->query("UPDATE `" . DB_PREFIX . "boxnow_requests` SET `request_id` = '" . (int)$request['id'] . "', `parcels` = '" . $this->db->escape(json_encode($request['parcels'])) . "', `locker_id` = '" . (int)$request['locker_id'] . "', `status` = '" . (int)$request['status_id'] . "', `status_message` = '" . $this->db->escape((string)$request['status_message']) . "' WHERE `order_id` = '" . (int)$order['order_id'] . "'");
		}
	}

	/**
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function cancelRequest(int $order_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "boxnow_requests` SET `status` = '3', `status_message` = 'Cancelled' WHERE `order_id` = '" . (int)$order_id . "'");
	}
}
