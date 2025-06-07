<?php
/**
 * Class Thumb
 *
 * @package Opencart\Catalog\Controller\Product
 */
class ControllerProductThumb extends Controller {
	/**
	 * @param array<string, mixed> $data
	 *
	 * @return string
	 */
	public function index(array $data): string {
		$this->load->language('product/thumb');

		$data['cart'] = $this->url->link('common/cart/info');
		$data['cart2'] = $this->url->link('common/cart/info2');
		$data['cart3'] = $this->url->link('common/cart3/info');

		$data['add_to_cart'] = $this->url->link('checkout/cart/add');
		$data['add_to_wishlist'] = $this->url->link('account/wishlist/add');
		$data['add_to_compare'] = $this->url->link('product/compare/add');

		$data['review_status'] = (int)$this->config->get('config_review_status');

		return $this->load->view('product/thumb', $data);
	}
}
