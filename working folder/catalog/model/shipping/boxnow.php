<?php
namespace Opencart\Catalog\Model\Extension\Boxnow\Shipping;
/**
 * Class Boxnow
 *
 * @package Opencart\Catalog\Model\Extension\Boxnow\Shipping
 */
class Boxnow extends \Opencart\System\Engine\Model {
	/**
	 * @param array $address
	 *
	 * @return array
	 */
	public function getQuote(array $address): array {
		$this->load->language('extension/boxnow/shipping/boxnow');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('shipping_boxnow_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		if (!$this->config->get('shipping_boxnow_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = [];

		if (!$status) {
			return $method_data;
		}

		// Category filter — hide BoxNow if a cart product belongs to a blocked category
		$blocked_categories = $this->config->get('shipping_boxnow_categories');

		if (!empty($blocked_categories) && is_array($blocked_categories)) {
			$cart_products = $this->cart->getProducts();
			$product_ids   = array_map(function ($product) {
				return (int)$product['product_id'];
			}, $cart_products);

			if ($product_ids) {
				$result = $this->db->query("SELECT COUNT(DISTINCT `product_id`) AS `matched` FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` IN (" . implode(',', $product_ids) . ") AND `category_id` IN (" . implode(',', array_map('intval', $blocked_categories)) . ")");

				if ((int)$result->row['matched'] > 0) {
					return $method_data;
				}
			}
		}

		// Shipping cost based on the configured weight ranges
		$weight = $this->cart->getWeight();

		$cost = 0;

		$weight_values = $this->config->get('shipping_boxnow_weight_value');

		if ($weight_values) {
			foreach ($weight_values as $weight_range) {
				$from  = (float)$weight_range['from'];
				$to    = (float)$weight_range['to'];
				$price = (float)$weight_range['price'];

				if ($weight >= $from && $weight <= $to) {
					$cost = $price;

					break;
				}
			}
		}

		$free_shipping = $this->config->get('shipping_boxnow_free_shipping');

		if (is_numeric($free_shipping) && $this->cart->getSubTotal() >= (float)$free_shipping) {
			$cost = 0;
		}

		$quote_data = [];

		$quote_data['boxnow'] = [
			'code'         => 'boxnow.boxnow',
			'name'         => $this->language->get('text_description'),
			'cost'         => $cost,
			'tax_class_id' => $this->config->get('shipping_boxnow_tax_class_id'),
			'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_boxnow_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
		];

		$method_data = [
			'code'       => 'boxnow',
			'name'       => $this->language->get('text_title'),
			'quote'      => $quote_data,
			'sort_order' => $this->config->get('shipping_boxnow_sort_order'),
			'error'      => false
		];

		return $method_data;
	}

	/**
	 * Records the locker chosen at checkout against an order. The checkout re-saves
	 * the same order several times (addOrder once, editOrder after every change), so
	 * an existing row is updated rather than duplicated — unless a waybill has
	 * already been created for it.
	 *
	 * @param int $order_id
	 * @param int $locker_id
	 *
	 * @return void
	 */
	public function setRequest(int $order_id, int $locker_id): void {
		if ($order_id && $locker_id) {
			$query = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "boxnow_requests` WHERE `order_id` = '" . (int)$order_id . "'");

			if ($query->num_rows) {
				$this->db->query("UPDATE `" . DB_PREFIX . "boxnow_requests` SET `locker_id` = '" . (int)$locker_id . "' WHERE `order_id` = '" . (int)$order_id . "' AND `status` = '2'");
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "boxnow_requests` SET `order_id` = '" . (int)$order_id . "', `request_id` = '0', `parcels` = '[]', `locker_id` = '" . (int)$locker_id . "', `status_message` = NULL, `status` = '2'");
			}
		}
	}
}
