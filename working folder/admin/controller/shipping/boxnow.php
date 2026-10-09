<?php
namespace Opencart\Admin\Controller\Extension\Boxnow\Shipping;
/**
 * Class Boxnow
 *
 * @package Opencart\Admin\Controller\Extension\Boxnow\Shipping
 */
class Boxnow extends \Opencart\System\Engine\Controller {
	/**
	 * Settings screen.
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/boxnow/shipping/boxnow');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/boxnow/shipping/boxnow', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/boxnow/shipping/boxnow.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');

		$fields = [
			'shipping_boxnow_api_url',
			'shipping_boxnow_client_id',
			'shipping_boxnow_client_secret',
			'shipping_boxnow_warehouse_number',
			'shipping_boxnow_partner_id',
			'shipping_boxnow_partner_name',
			'shipping_boxnow_partner_email',
			'shipping_boxnow_partner_phone',
			'shipping_boxnow_geo_zone_id',
			'shipping_boxnow_status',
			'shipping_boxnow_sort_order',
			'shipping_boxnow_weight',
			'shipping_boxnow_weight_type',
			'shipping_boxnow_default_weight',
			'shipping_boxnow_free_shipping',
			'shipping_boxnow_tax_class_id'
		];

		foreach ($fields as $field) {
			$data[$field] = $this->config->get($field);
		}

		$data['shipping_boxnow_weight_value'] = [];

		$weight_values = $this->config->get('shipping_boxnow_weight_value');

		if ($weight_values) {
			foreach ($weight_values as $weight_value) {
				$data['shipping_boxnow_weight_value'][] = [
					'from'  => number_format((float)$weight_value['from'], 2, '.', ''),
					'to'    => number_format((float)$weight_value['to'], 2, '.', ''),
					'price' => number_format((float)$weight_value['price'], 2, '.', '')
				];
			}
		}

		$data['shipping_boxnow_categories'] = (array)$this->config->get('shipping_boxnow_categories');
		$data['shipping_boxnow_payment_modules'] = (array)$this->config->get('shipping_boxnow_payment_modules');

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$this->load->model('catalog/category');

		$data['oc_categories'] = $this->model_catalog_category->getCategories();

		$this->load->model('setting/extension');

		$data['payment_modules'] = [];

		$payment_extensions = $this->model_setting_extension->getExtensionsByType('payment');

		foreach ($payment_extensions as $payment_extension) {
			if ($this->config->get('payment_' . $payment_extension['code'] . '_status')) {
				$this->load->language('extension/' . $payment_extension['extension'] . '/payment/' . $payment_extension['code'], $payment_extension['code']);

				$data['payment_modules'][] = [
					'name' => $this->language->get($payment_extension['code'] . '_heading_title'),
					'code' => $payment_extension['code']
				];
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/boxnow/shipping/boxnow', $data));
	}

	/**
	 * Settings save (AJAX).
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/boxnow/shipping/boxnow');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/boxnow/shipping/boxnow')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$post = $this->request->post;

			// Weight ranges
			$weight_values = [];

			if (!empty($post['shipping_boxnow_weight_value'])) {
				foreach ($post['shipping_boxnow_weight_value'] as $weight_value) {
					$weight_values[] = [
						'from'  => (float)str_replace(',', '.', $weight_value['from']),
						'to'    => (float)str_replace(',', '.', $weight_value['to']),
						'price' => isset($weight_value['price']) ? (float)str_replace(',', '.', $weight_value['price']) : 0
					];
				}
			}

			$post['shipping_boxnow_weight_value'] = $weight_values;

			// No category checked still needs to be saved as an empty array
			if (!isset($post['shipping_boxnow_categories'])) {
				$post['shipping_boxnow_categories'] = [];
			}

			if (!isset($post['shipping_boxnow_payment_modules'])) {
				$post['shipping_boxnow_payment_modules'] = [];
			}

			// Normalise the partner phone number to +359XXXXXXXXX
			if (!empty($post['shipping_boxnow_partner_phone'])) {
				$phone = str_replace([' ', '-'], '', $post['shipping_boxnow_partner_phone']);
				$phone = preg_replace('/^(?:\+?359|0)?/', '+359', $phone);

				$post['shipping_boxnow_partner_phone'] = preg_match('/^\+359\d{9}$/', $phone) ? $phone : '';
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('shipping_boxnow', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Saves the locker ID against an order (AJAX), independently of creating a
	 * waybill — called as soon as a locker is picked from the map or typed in,
	 * so the choice is never lost even if "Create waybill" is never clicked.
	 *
	 * @return void
	 */
	public function saveLocker(): void {
		$this->load->language('extension/boxnow/shipping/boxnow');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/boxnow/shipping/boxnow')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$order_id = isset($this->request->post['order_id']) ? (int)$this->request->post['order_id'] : 0;
			$locker_id = isset($this->request->post['locker_id']) ? (int)$this->request->post['locker_id'] : 0;

			if ($order_id && $locker_id) {
				$this->load->model('extension/boxnow/shipping/boxnow');

				$this->model_extension_boxnow_shipping_boxnow->saveLockerId($order_id, $locker_id);

				$json['success'] = true;
			} else {
				$json['error'] = true;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * BOX NOW orders report.
	 *
	 * @return void
	 */
	public function report(): void {
		$this->load->language('extension/boxnow/shipping/boxnow');
		$this->load->language('sale/order');

		$this->load->model('extension/boxnow/shipping/boxnow');
		$this->load->model('sale/order');

		$this->document->setTitle($this->language->get('heading_title_report'));

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['orders'] = [];

		$filter_data = [
			'start' => ($page - 1) * $this->config->get('config_pagination_admin'),
			'limit' => $this->config->get('config_pagination_admin')
		];

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_report'),
			'href' => $this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token'])
		];

		$order_total = $this->model_extension_boxnow_shipping_boxnow->getBoxNowTotalOrders($filter_data);

		$results = $this->model_extension_boxnow_shipping_boxnow->getBoxNowOrders($filter_data);

		foreach ($results as $result) {
			$boxnow_info = $this->model_extension_boxnow_shipping_boxnow->getBoxNowStatus((int)$result['order_id']);

			if ($boxnow_info) {
				$boxnow_request_id      = $boxnow_info['request_id'];
				$boxnow_parcels         = json_decode($boxnow_info['parcels'], true);
				$boxnow_status_message  = $boxnow_info['status_message'];
				$boxnow_locker_id       = $boxnow_info['locker_id'];
				$boxnow_status          = $boxnow_info['status'];
			} else {
				$boxnow_request_id     = '';
				$boxnow_parcels        = [];
				$boxnow_status_message = '';
				$boxnow_locker_id      = '';
				$boxnow_status         = '';
			}

			$order_total_products = 0;

			$order_products = $this->model_sale_order->getProducts((int)$result['order_id']);

			foreach ($order_products as $order_product) {
				$order_total_products += $order_product['quantity'];
			}

			$data['orders'][] = [
				'order_id'              => $result['order_id'],
				'customer'              => $result['customer'],
				'order_status'          => $result['order_status'] ?: $this->language->get('text_missing'),
				'total'                 => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'            => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_modified'         => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
				'products'              => $order_total_products,
				'telephone'             => $result['telephone'],
				'boxnow_request_id'     => $boxnow_request_id,
				'boxnow_parcels'        => $boxnow_parcels,
				'boxnow_status_message' => $boxnow_status_message,
				'boxnow_locker_id'      => $boxnow_locker_id,
				'boxnow_status'         => $boxnow_status,
				'boxnow_submit'         => $this->url->link('extension/boxnow/shipping/boxnow.deliveryRequests', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id']),
				'boxnow_cancel'         => $this->url->link('extension/boxnow/shipping/boxnow.cancelDelivery', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id']),
				'view'                  => $this->url->link('sale/order.info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'])
			];
		}

		$warehouse_number_array = $this->getWarehouseNumbers();

		$data['warehouse_number'] = $warehouse_number_array;
		$data['partner_id'] = $this->config->get('shipping_boxnow_partner_id');
		// Third argument (js = true) keeps the raw "&" — this URL is used from fetch(),
		// not from an HTML attribute, so "&amp;" would drop the user_token parameter.
		$data['save_locker'] = $this->url->link('extension/boxnow/shipping/boxnow.saveLocker', 'user_token=' . $this->session->data['user_token'], true);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $order_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($order_total - $this->config->get('config_pagination_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $order_total, ceil($order_total / $this->config->get('config_pagination_admin')));

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['error'])) {
			$data['error'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} else {
			$data['error'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/boxnow/shipping/boxnow_list', $data));
	}

	/**
	 * Creates a delivery request (waybill) with the BOX NOW API for an order.
	 *
	 * @return void
	 */
	public function deliveryRequests(): void {
		if (!$this->user->hasPermission('modify', 'extension/boxnow/shipping/boxnow') || empty($this->request->get['order_id'])) {
			$this->response->redirect($this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$this->load->language('extension/boxnow/shipping/boxnow');
		$this->load->model('sale/order');
		$this->load->model('extension/boxnow/shipping/boxnow');

		$order_id = (int)$this->request->get['order_id'];

		$order = $this->model_sale_order->getOrder($order_id);
		$boxnow_data = $this->model_extension_boxnow_shipping_boxnow->getBoxNowStatus($order_id);

		$quantity = (isset($this->request->get['quantity']) && $this->request->get['quantity'] > 1) ? (int)$this->request->get['quantity'] : 1;

		$total_weight = $this->model_extension_boxnow_shipping_boxnow->getOrderWeight($order_id);
		$weight_per_item = $total_weight / $quantity;

		$locker_id = !empty($this->request->get['locker_id']) ? (int)$this->request->get['locker_id'] : (int)($boxnow_data['locker_id'] ?? 0);

		// Persist the chosen locker straight away — whether picked from the map or
		// typed manually — so it is never lost even if the API call below fails, and
		// regardless of whether the storefront checkout ever recorded a row itself.
		$this->model_extension_boxnow_shipping_boxnow->saveLockerId($order_id, $locker_id);

		// No locker at all — BOX NOW would only answer with a generic P402
		if (!$locker_id) {
			$this->session->data['error'] = sprintf($this->language->get('error_order_locker'), $order_id);

			$this->response->redirect($this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$warehouse_number =$this->request->get['warehouse_number'] ?? '';

		if (!$warehouse_number) {
			$warehouse_number_array = $this->getWarehouseNumbers();
			$keys = array_keys($warehouse_number_array);
			$warehouse_number = reset($keys);
		}

		$access_token = $this->getAccessToken();

		$compartment_size = isset($this->request->get['compartment_size']) ? (int)$this->request->get['compartment_size'] : 3;

		if (!in_array($compartment_size, [1, 2, 3])) {
			$compartment_size = 3;
		}

		$items = [];

		for ($x = 1; $x <= $quantity; $x++) {
			$items[] = [
				'value'           => number_format(0, 2, '.', ''),
				'weight'          => $weight_per_item,
				'compartmentSize' => $compartment_size
			];
		}

		// A phone typed into the report row takes priority over the one on the order
		$telephone_input = trim((string)($this->request->get['telephone'] ?? ''));

		$telephone = $telephone_input ?: trim((string)$order['telephone']);

		// The OC4 checkout hides the telephone field unless it is enabled in the store
		// settings, so the order itself can be without one — fall back to the phone
		// on the customer's account.
		if (!$telephone && !empty($order['customer_id'])) {
			$this->load->model('customer/customer');

			$customer_info = $this->model_customer_customer->getCustomer((int)$order['customer_id']);

			$telephone = trim((string)($customer_info['telephone'] ?? ''));
		}

		$phone = preg_replace('/[^\d+]/', '', $telephone);
		$phone = preg_replace('/^(?:(?:\+|00)?359|0)?/', '+359', $phone);

		// No usable phone — BOX NOW would reject it with P405, so say what is actually
		// missing instead of sending a bare "+359".
		if (!preg_match('/^\+359\d{7,9}$/', $phone)) {
			$this->session->data['error'] = sprintf($this->language->get('error_order_telephone'), $order_id);

			$this->response->redirect($this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		// Keep the phone on the order itself, so it does not have to be typed again
		if ($phone !== trim((string)$order['telephone'])) {
			$this->model_extension_boxnow_shipping_boxnow->saveOrderTelephone($order_id, $phone);
		}

		// OC4 has no payment_code column — the payment method is a JSON column decoded
		// to ['name' => ..., 'code' => 'cod.cod'] (extension.method).
		$payment_code = explode('.', (string)($order['payment_method']['code'] ?? ''))[0];

		// The BOX NOW schema expects orderNumber and both locationId values as JSON
		// strings (as the OC3 module sent them), not numbers.
		$data = [
			'orderNumber'          => (string)$order['order_id'],
			'invoiceValue'         => number_format($order['total'], 2, '.', ''),
			'paymentMode'          => ($payment_code == 'cod') ? 'cod' : 'prepaid',
			'amountToBeCollected'  => number_format($order['total'], 2, '.', ''),
			'allowReturn'          => true,
			'origin'               => [
				'contactName'   => (string)$this->config->get('shipping_boxnow_partner_name'),
				'contactEmail'  => (string)$this->config->get('shipping_boxnow_partner_email'),
				'contactNumber' => (string)$this->config->get('shipping_boxnow_partner_phone'),
				'locationId'    => (string)$warehouse_number
			],
			'destination'          => [
				'contactNumber' => $phone,
				'contactEmail'  => (string)$order['email'],
				'contactName'   => trim($order['shipping_firstname'] . ' ' . $order['shipping_lastname']),
				'locationId'    => (string)$locker_id
			],
			'items' => $items
		];

		$response = $this->curlRequest('POST', '/api/v1/delivery-requests', $data, $access_token);
		$response_data = json_decode((string)$response, true);

		if (!is_array($response_data)) {
			$response_data = [];
		}

		// On failure, keep the exact request/response in the OpenCart error log
		// (System > Maintenance > Error Logs) so the rejected field can be identified.
		if (!isset($response_data['id']) || isset($response_data['status'])) {
			$this->log->write('BOX NOW delivery-requests failed. Request: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . ' Response: ' . $response);
		}

		if (isset($response_data['id']) && !isset($response_data['status'])) {
			$response_data['status_id'] = 1;
			$response_data['status_message'] = null;
			$response_data['locker_id'] = $locker_id;

			$this->model_extension_boxnow_shipping_boxnow->updateRequest($order, $response_data);

			$this->session->data['success'] = $this->language->get('text_voucher_status_success');
		} else {
			$error_codes = $this->getErrorCodes();

			// BOX NOW's own message/details (if any) are appended so the exact field
			// that failed validation can be identified — the static text per code is
			// only a general hint.
			$api_detail = '';

			if (!empty($response_data['message'])) {
				$api_detail = ' ' . $response_data['message'];
			}

			if (!empty($response_data['errors'])) {
				$api_detail .= ' ' . json_encode($response_data['errors'], JSON_UNESCAPED_UNICODE);
			}

			if (!empty($response_data['jsonSchemaErrors'])) {
				$api_detail .= ' ' . json_encode($response_data['jsonSchemaErrors'], JSON_UNESCAPED_UNICODE);
			}

			$response_data['id'] = 0;
			$response_data['status_id'] = 2;
			$response_data['parcels'] = [];
			$response_data['locker_id'] = $locker_id;
			$response_data['status_message'] = sprintf($this->language->get('status_message_error'), $response_data['code'] ?? '', $error_codes[$response_data['code'] ?? ''] ?? '') . $api_detail;

			$this->model_extension_boxnow_shipping_boxnow->updateRequest($order, $response_data);

			$this->session->data['error'] = $response_data['status_message'];
		}

		$this->response->redirect($this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token']));
	}

	/**
	 * Cancels a previously created delivery request.
	 *
	 * @return void
	 */
	public function cancelDelivery(): void {
		if (!$this->user->hasPermission('modify', 'extension/boxnow/shipping/boxnow') || empty($this->request->get['order_id'])) {
			$this->response->redirect($this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$this->load->language('extension/boxnow/shipping/boxnow');
		$this->load->model('extension/boxnow/shipping/boxnow');

		$order_id = (int)$this->request->get['order_id'];

		$boxnow_data = $this->model_extension_boxnow_shipping_boxnow->getBoxNowStatus($order_id);

		if ($boxnow_data && !empty($boxnow_data['parcels'])) {
			$parcels = json_decode($boxnow_data['parcels'], true);

			$access_token = $this->getAccessToken();

			$all_cancelled = true;

			if (is_array($parcels)) {
				foreach ($parcels as $parcel) {
					$response = $this->curlRequest('POST', '/api/v1/parcels/' . $parcel['id'] . ':cancel', [], $access_token);
					$cancel_data = $response ? json_decode($response, true) : [];

					if (isset($cancel_data['success']) && $cancel_data['success'] !== true) {
						$all_cancelled = false;
					}
				}
			}

			if ($all_cancelled) {
				$this->model_extension_boxnow_shipping_boxnow->cancelRequest($order_id);

				$this->session->data['success'] = $this->language->get('text_voucher_cancel_success');
			} else {
				$this->session->data['error'] = $this->language->get('text_voucher_cancel_error');
			}
		}

		$this->response->redirect($this->url->link('extension/boxnow/shipping/boxnow.report', 'user_token=' . $this->session->data['user_token']));
	}

	/**
	 * Streams the shipping label PDF for a parcel.
	 *
	 * @return void
	 */
	public function getParcel(): void {
		if (!$this->user->hasPermission('access', 'extension/boxnow/shipping/boxnow') || empty($this->request->get['parcel_id'])) {
			return;
		}

		$parcel_id = $this->request->get['parcel_id'];
		$access_token = $this->getAccessToken();

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL            => $this->config->get('shipping_boxnow_api_url') . '/api/v1/parcels/' . $parcel_id . '/label.pdf',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'GET',
			CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token, 'Content-Type: application/pdf']
		]);

		$response = curl_exec($curl);

		curl_close($curl);

		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename=' . $parcel_id . '.pdf');

		echo $response;
	}

	/**
	 * Creates the boxnow_requests table and registers the storefront/admin events.
	 *
	 * @return void
	 */
	public function install(): void {
		$this->load->model('extension/boxnow/shipping/boxnow');

		$this->model_extension_boxnow_shipping_boxnow->install();

		$this->load->model('setting/event');

		foreach ($this->getEvents() as $event) {
			$this->model_setting_event->deleteEventByCode($event['code']);

			$this->model_setting_event->addEvent([
				'code'        => $event['code'],
				'description' => 'BOX NOW Delivery',
				'trigger'     => $event['trigger'],
				'action'      => $event['action'],
				'status'      => true,
				'sort_order'  => 0
			]);
		}
	}

	/**
	 * Drops the boxnow_requests table and unregisters the events.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('extension/boxnow/shipping/boxnow');

		$this->model_extension_boxnow_shipping_boxnow->uninstall();

		$this->load->model('setting/event');

		foreach ($this->getEvents() as $event) {
			$this->model_setting_event->deleteEventByCode($event['code']);
		}
	}

	/**
	 * @return array
	 */
	private function getEvents(): array {
		return [
			['code' => 'boxnow_order', 'trigger' => 'catalog/model/checkout/order/addOrder/after', 'action' => 'extension/boxnow/event/boxnow.addOrder'],
			['code' => 'boxnow_order_edit', 'trigger' => 'catalog/model/checkout/order/editOrder/after', 'action' => 'extension/boxnow/event/boxnow.editOrder'],
			['code' => 'boxnow_payment', 'trigger' => 'catalog/model/checkout/payment_method/getMethods/after', 'action' => 'extension/boxnow/event/boxnow.paymentMethods'],
			['code' => 'boxnow_success', 'trigger' => 'catalog/controller/checkout/success/before', 'action' => 'extension/boxnow/event/boxnow.success'],
			['code' => 'boxnow_shipping_widget', 'trigger' => 'catalog/view/checkout/shipping_method/after', 'action' => 'extension/boxnow/event/boxnow.shippingMethodView'],
			['code' => 'boxnow_column_left', 'trigger' => 'admin/view/common/column_left/before', 'action' => 'extension/boxnow/event/boxnow.columnLeft']
		];
	}

	/**
	 * @return array
	 */
	private function getWarehouseNumbers(): array {
		$warehouse_number = (string)$this->config->get('shipping_boxnow_warehouse_number');
		$rows = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $warehouse_number)));

		$warehouse_number_array = [];

		foreach ($rows as $row) {
			$parts = array_map('trim', explode(':', $row));

			$warehouse_number_array[$parts[0]] = $parts[1] ?? ('Warehouse #' . $parts[0]);
		}

		return $warehouse_number_array;
	}

	/**
	 * @return string
	 */
	private function getAccessToken(): string {
		$response = $this->curlRequest('POST', '/api/v1/auth-sessions', [
			'grant_type'    => 'client_credentials',
			'client_id'     => $this->config->get('shipping_boxnow_client_id'),
			'client_secret' => $this->config->get('shipping_boxnow_client_secret')
		]);

		$json = json_decode($response, true);

		return $json['access_token'] ?? '';
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param array  $data
	 * @param string $access_token
	 *
	 * @return string|false
	 */
	private function curlRequest(string $method, string $path, array $data = [], string $access_token = '') {
		$headers = ['Content-Type: application/json'];

		if ($access_token) {
			$headers[] = 'Authorization: Bearer ' . $access_token;
		}

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL            => $this->config->get('shipping_boxnow_api_url') . $path,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_POSTFIELDS     => json_encode($data ?: new \stdClass())
		]);

		$response = curl_exec($curl);

		curl_close($curl);

		return $response;
	}

	/**
	 * @return array
	 */
	private function getErrorCodes(): array {
		return [
			'P400' => 'Заявка с грешни данни. Информацията изпратена от Вас не може да бъде валидирана.',
			'P401' => 'Заявка с грешна начална точка на пратката. Уверете се, че ползвате валиден ID на локацията, от която BOX NOW ще вземе пратката.',
			'P402' => 'Невалидна крайна дестинация! Уверете се, че използвате правилното ID на локацията (Номер на BOX NOW автомат) и че подадените данни са коректни.',
			'P403' => 'Не Ви е позволено да ползвате доставки от типа (Всеки Автомат към същия автомат) AnyAPM - SameAPM. Обърнете се към техническата поддръжка, ако считате, че това е невярно',
			'P404' => 'Невалиден CSV импорт. Вижте съдържанието на грешката за повече информация.',
			'P405' => 'Невалиден телефонен номер. Проверете дали изпращате телефон в подходящ интернационален формат, пример: +359 xx xxx xxxx.',
			'P406' => 'Невалиден размер. Уверете се, че в заявката си изпращате някой от необходимите размери 1, 2 или 3 (Малък, Среден или Голям).',
			'P407' => 'Невалиден код за държавата. Уверете се, че изпращате коректен код за държава във формат по ISO 3166-1alpha-2. Пример: BG',
			'P408' => 'Невалидна стойност на поръчката. Уверете се, че Вашата поръчка е в допустимите граници, между 0 и 5000',
			'P409' => 'Невалидна референция на партньора. Уверете се, че реферирате валидно ID на партньор.',
			'P410' => 'Конфликт в номера на поръчката. Моля използвайте друго ID за поръчката.',
			'P411' => 'Вие не можете да използвате „наложен платеж“ като тип за плащане.',
			'P412' => 'Вие не можете да създадете заявка за връщане на пратка.',
			'P420' => 'Не е възможно отказването на пратката.',
			'P430' => 'Тази пратка не е в готовност за AnyAPM потвърждение.'
		];
	}
}
