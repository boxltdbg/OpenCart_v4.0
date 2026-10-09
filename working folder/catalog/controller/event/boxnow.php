<?php
namespace Opencart\Catalog\Controller\Extension\Boxnow\Event;
/**
 * Class Boxnow
 *
 * Front-end event hooks for the BOX NOW shipping extension. Registered against core
 * triggers at install time (see Admin\Controller\Extension\Boxnow\Shipping\Boxnow::install),
 * so no core file ever needs to be modified.
 *
 * @package Opencart\Catalog\Controller\Extension\Boxnow\Event
 */
class Boxnow extends \Opencart\System\Engine\Controller {
	/**
	 * catalog/model/checkout/order/addOrder/after
	 *
	 * Records the locker chosen during checkout against the freshly created order.
	 *
	 * @param string $route
	 * @param array  $args
	 * @param mixed  $output
	 *
	 * @return void
	 */
	public function addOrder(string &$route, array &$args, mixed &$output): void {
		$order_data = $args[0] ?? [];

		$shipping_code = $order_data['shipping_method']['code'] ?? '';

		if ($shipping_code === 'boxnow.boxnow' && !empty($this->session->data['boxnow_locker_id']) && $output) {
			$this->load->model('extension/boxnow/shipping/boxnow');

			$this->model_extension_boxnow_shipping_boxnow->setRequest((int)$output, (int)$this->session->data['boxnow_locker_id']);
		}
	}

	/**
	 * catalog/model/checkout/order/editOrder/after
	 *
	 * The checkout updates an already created (still unconfirmed) order through
	 * editOrder — keep the locker in sync with whatever is chosen at that point.
	 *
	 * @param string $route
	 * @param array  $args
	 * @param mixed  $output
	 *
	 * @return void
	 */
	public function editOrder(string &$route, array &$args, mixed &$output): void {
		$order_id = (int)($args[0] ?? 0);

		$shipping_code = $args[1]['shipping_method']['code'] ?? '';

		if ($shipping_code === 'boxnow.boxnow' && !empty($this->session->data['boxnow_locker_id']) && $order_id) {
			$this->load->model('extension/boxnow/shipping/boxnow');

			$this->model_extension_boxnow_shipping_boxnow->setRequest($order_id, (int)$this->session->data['boxnow_locker_id']);
		}
	}

	/**
	 * catalog/model/checkout/payment_method/getMethods/after
	 *
	 * Restricts the available payment methods to the whitelist configured for BOX NOW
	 * (e.g. hides "Cash on delivery" if it cannot be combined with locker delivery).
	 *
	 * @param string $route
	 * @param array  $args
	 * @param mixed  $output
	 *
	 * @return void
	 */
	public function paymentMethods(string &$route, array &$args, mixed &$output): void {
		if (!is_array($output) || empty($this->session->data['shipping_method']['code']) || $this->session->data['shipping_method']['code'] !== 'boxnow.boxnow') {
			return;
		}

		$allowed = (array)$this->config->get('shipping_boxnow_payment_modules');

		if (!$allowed) {
			return;
		}

		$output = array_filter($output, function ($code) use ($allowed) {
			return in_array($code, $allowed);
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * catalog/controller/checkout/success/before
	 *
	 * Clears the BOX NOW locker selection once the order has been placed.
	 *
	 * @param string $route
	 * @param array  $args
	 *
	 * @return void
	 */
	public function success(string &$route, array &$args): void {
		// Last chance to record the locker. addOrder only fires the first time the
		// checkout saves an order; if the customer picks BOX NOW (or another locker)
		// after that, the core calls editOrder instead and the choice would be lost.
		// This event runs before the core success controller clears the session.
		if (!empty($this->session->data['order_id']) && !empty($this->session->data['boxnow_locker_id']) && ($this->session->data['shipping_method']['code'] ?? '') === 'boxnow.boxnow') {
			$this->load->model('extension/boxnow/shipping/boxnow');

			$this->model_extension_boxnow_shipping_boxnow->setRequest((int)$this->session->data['order_id'], (int)$this->session->data['boxnow_locker_id']);
		}

		unset($this->session->data['boxnow_locker_id']);
		unset($this->session->data['boxnow_address']);
		unset($this->session->data['boxnow_name']);
	}

	/**
	 * catalog/view/checkout/shipping_method/after
	 *
	 * Appends the BOX NOW locker map widget + gating script to the rendered
	 * checkout/shipping_method template, without touching the core template file.
	 *
	 * @param string $route
	 * @param array  $data
	 * @param string $output
	 *
	 * @return void
	 */
	public function shippingMethodView(string &$route, array &$data, string &$output): void {
		$this->load->language('extension/boxnow/shipping/boxnow');

		$strings = [
			'text_select_lockerid' => $this->language->get('text_select_lockerid'),
			'error_boxnow'         => $this->language->get('error_boxnow'),
			'selected_boxnow'      => $this->language->get('selected_boxnow')
		];

		$config = [
			'partnerId' => (string)$this->config->get('shipping_boxnow_partner_id'),
			'zip'       => $this->session->data['shipping_address']['postcode'] ?? ''
		];

		$state = [
			'selected' => !empty($this->session->data['boxnow_locker_id']),
			'address'  => $this->session->data['boxnow_address'] ?? '',
			'name'     => $this->session->data['boxnow_name'] ?? ''
		];

		$set_locker_url = $this->url->link('extension/boxnow/shipping/boxnow.setLockerSession', 'language=' . $this->config->get('config_language'), true);

		// The vendor widget sizes itself against its target element at the moment its
		// script executes. If that element doesn't exist yet, or is display:none / has
		// zero dimensions at that moment, the map silently fails to render. So rather
		// than creating #boxnowmap on demand (too late, inside a modal that gets
		// recreated), we create ONE persistent #boxnowmap element as soon as this
		// script runs, load the vendor script against it once while it's parked
		// off-screen (but fully laid out, not display:none), and then just move that
		// same, already-initialised element into/out of the modal as the user toggles
		// the shipping method — never destroying or recreating it.
		$output .= '
<div id="boxnow-widget-template" hidden>
	<div class="alert alert-info">' . htmlspecialchars($strings['text_select_lockerid'], ENT_QUOTES) . '</div>
	<div class="boxnow-map-anchor"></div>
	<div id="boxnow-messages" class="mt-2"></div>
</div>
<script type="text/javascript"><!--
(function() {
	var bnStrings = ' . json_encode($strings) . ';
	var bnConfig  = ' . json_encode($config) . ';
	var bnState   = ' . json_encode($state) . ';
	var bnSetLockerUrl = ' . json_encode($set_locker_url) . ';
	var bnLockerId = null;
	var bnMapEl = null;

	function bnParkMap() {
		if (!bnMapEl) {
			return;
		}

		bnMapEl.style.position = "fixed";
		bnMapEl.style.left = "-9999px";
		bnMapEl.style.top = "0";
		bnMapEl.style.width = "600px";
		bnMapEl.style.height = "420px";

		if (bnMapEl.parentNode !== document.body) {
			document.body.appendChild(bnMapEl);
		}
	}

	function bnMountMap(anchor) {
		if (!bnMapEl || !anchor) {
			return;
		}

		bnMapEl.style.position = "static";
		bnMapEl.style.left = "";
		bnMapEl.style.top = "";
		bnMapEl.style.width = "100%";
		bnMapEl.style.height = "420px";

		anchor.appendChild(bnMapEl);
	}

	function bnInitMap() {
		bnMapEl = document.createElement("div");
		bnMapEl.id = "boxnowmap";

		bnParkMap();

		window._bn_map_widget_config = {
			partnerId: bnConfig.partnerId,
			zip: bnConfig.zip,
			autoshow: true,
			parentElement: "#boxnowmap",
			afterSelect: bnSetLocker
		};

		var script = document.createElement("script");

		script.src = "https://widget-cdn.boxnow.bg/map-widget/client/v4.js";
		script.async = true;

		document.getElementsByTagName("head")[0].appendChild(script);
	}

	// Initialise as early as possible — well before the user ever opens the
	// shipping-method dialog — so the vendor widget has time to fully load and
	// size itself correctly.
	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", bnInitMap);
	} else {
		bnInitMap();
	}

	function bnInjectWidget() {
		if (document.getElementById("boxnow-widget-container")) {
			return false;
		}

		var template = document.getElementById("boxnow-widget-template");
		var form = document.getElementById("form-shipping-method");

		if (!template || !form) {
			return false;
		}

		var container = document.createElement("div");

		container.id = "boxnow-widget-container";
		container.className = "mb-3";
		container.innerHTML = template.innerHTML;

		var anchor = form.querySelector(".text-end");

		if (anchor) {
			form.insertBefore(container, anchor);
		} else {
			form.appendChild(container);
		}

		if (bnState.selected) {
			bnLockerId = true;

			var messages = container.querySelector("#boxnow-messages");

			if (messages) {
				messages.innerHTML = \'<div class="alert alert-success">\' + bnStrings.selected_boxnow + \' \' + bnState.address + \' [\' + bnState.name + \']</div>\';
			}
		}

		return true;
	}

	function bnSetLocker(selected) {
		bnLockerId = selected.boxnowLockerId;

		fetch(bnSetLockerUrl, {
			method: "POST",
			headers: {"Content-Type": "application/x-www-form-urlencoded"},
			body: "locker_id=" + encodeURIComponent(selected.boxnowLockerId) + "&locker_address=" + encodeURIComponent(selected.boxnowLockerAddressLine1) + "&locker_name=" + encodeURIComponent(selected.boxnowLockerName)
		}).then(function() {
			var messages = document.getElementById("boxnow-messages");

			if (messages) {
				messages.innerHTML = \'<div class="alert alert-success">\' + bnStrings.selected_boxnow + \' \' + selected.boxnowLockerAddressLine1 + \' [\' + selected.boxnowLockerName + \']</div>\';
			}

			bnUpdateGate();
		});
	}

	function bnUpdateGate() {
		var checked = document.querySelector(\'input[name="shipping_method"]:checked\');
		var button = document.getElementById("button-shipping-method");

		if (!button) {
			return;
		}

		button.disabled = !!(checked && checked.value === "boxnow.boxnow" && !bnLockerId);
	}

	function bnToggle() {
		var checked = document.querySelector(\'input[name="shipping_method"]:checked\');
		var container = document.getElementById("boxnow-widget-container");

		if (checked && checked.value === "boxnow.boxnow") {
			if (!container) {
				bnInjectWidget();

				container = document.getElementById("boxnow-widget-container");
			}

			if (container) {
				container.hidden = false;

				bnMountMap(container.querySelector(".boxnow-map-anchor"));
			}
		} else {
			bnParkMap();

			if (container) {
				container.hidden = true;
			}
		}

		bnUpdateGate();
	}

	document.addEventListener("shown.bs.modal", function(e) {
		if (e.target && e.target.id === "modal-shipping") {
			bnToggle();
		}
	});

	document.addEventListener("hidden.bs.modal", function(e) {
		if (e.target && e.target.id === "modal-shipping") {
			// Safety net: rescue the map before the core checkout script removes
			// the whole modal element on the next "Choose" click.
			bnParkMap();
		}
	});

	document.addEventListener("change", function(e) {
		if (e.target && e.target.name === "shipping_method") {
			bnToggle();
		}
	});
})();
//--></script>';
	}
}
