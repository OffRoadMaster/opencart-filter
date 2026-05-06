<?php
namespace Opencart\Catalog\Controller\Extension\AJAXaf\Module;

class AttributeFilter extends \Opencart\System\Engine\Controller {
    public function index(array $setting): string {
        if (empty($setting['status'])) {
            return '';
        }

        $this->load->language('extension/AJAXaf/module/attribute_filter');
        $this->load->model('extension/AJAXaf/module/attribute_filter');

        $category_id = $this->getCategoryId();

        if (!$category_id) {
            return '';
        }

        $attribute_ids = array_map('intval', $setting['attribute_ids'] ?? []);
        $groups = $this->model_extension_AJAXaf_module_attribute_filter->getFilterGroups($category_id, (int)$this->config->get('config_language_id'), $attribute_ids);

        if (!$groups) {
            return '';
        }

        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'text_clear' => $this->language->get('text_clear'),
            'show_counts' => !empty($setting['show_counts']),
            'groups' => $groups,
            'ajax_url' => $this->url->link('extension/AJAXaf/module/attribute_filter.ajax'),
            'category_id' => $category_id,
            'limit' => max(1, (int)($setting['limit'] ?? 24))
        ];

        return $this->load->view('extension/AJAXaf/module/attribute_filter', $data);
    }

    public function ajax(): void {
        $this->load->language('extension/AJAXaf/module/attribute_filter');
        $this->load->model('extension/AJAXaf/module/attribute_filter');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $category_id = (int)($this->request->get['category_id'] ?? $this->getCategoryId());
        $limit = max(1, min(100, (int)($this->request->get['limit'] ?? 24)));
        $filters = $this->request->get['attribute_filter'] ?? [];
        $product_ids = $category_id ? $this->model_extension_AJAXaf_module_attribute_filter->getProductIds($category_id, (int)$this->config->get('config_language_id'), $filters) : [];
        $products = [];

        foreach (array_slice($product_ids, 0, $limit) as $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                $products[] = $this->formatProduct($product_info);
            }
        }

        $html = '';

        foreach ($products as $product) {
            $html .= $this->load->view('product/thumb', ['product' => $product]);
        }

        if (!$html) {
            $html = '<div class="col-12"><p class="text-muted">' . $this->language->get('text_no_results') . '</p></div>';
        }

        $json = [
            'total' => count($product_ids),
            'text_total' => sprintf($this->language->get('text_products'), count($product_ids)),
            'products' => $html
        ];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getCategoryId(): int {
        if (!empty($this->request->get['path'])) {
            $parts = explode('_', (string)$this->request->get['path']);

            return (int)end($parts);
        }

        return (int)($this->request->get['category_id'] ?? 0);
    }

    private function formatProduct(array $product_info): array {
        $image_width = (int)$this->config->get('config_image_product_width') ?: 300;
        $image_height = (int)$this->config->get('config_image_product_height') ?: 300;
        $image = $product_info['image'] ? $product_info['image'] : 'placeholder.png';

        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
        } else {
            $price = false;
        }

        if ((float)$product_info['special']) {
            $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
        } else {
            $special = false;
        }

        if ($this->config->get('config_tax')) {
            $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
        } else {
            $tax = false;
        }

        return [
            'product_id' => $product_info['product_id'],
            'thumb' => $this->model_tool_image->resize($image, $image_width, $image_height),
            'name' => $product_info['name'],
            'description' => oc_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->config->get('config_product_description_length')) . '..',
            'price' => $price,
            'special' => $special,
            'tax' => $tax,
            'minimum' => $product_info['minimum'] > 0 ? $product_info['minimum'] : 1,
            'rating' => $product_info['rating'],
            'href' => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
        ];
    }
}
