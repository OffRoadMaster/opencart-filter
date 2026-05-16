<?php
namespace Opencart\Admin\Controller\Extension\OpencartFilter\Module;

class AttributeFilter extends \Opencart\System\Engine\Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('extension/opencart_filter/module/attribute_filter');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/module');
        $this->load->model('extension/opencart_filter/module/attribute_filter');

        $data = [];
        foreach ([
            'heading_title', 'text_home', 'text_extension', 'text_success', 'text_edit', 'text_rebuild_success',
            'text_all_attributes', 'text_enabled', 'text_disabled', 'entry_status', 'entry_name',
            'entry_attribute', 'entry_auto_index', 'entry_show_counts', 'entry_limit', 'button_save',
            'button_back', 'button_rebuild', 'help_attribute', 'help_auto_index', 'help_limit'
        ] as $key) {
            $data[$key] = $this->language->get($key);
        }

        $data['user_token'] = $this->session->data['user_token'];
        $module_id = (int)($this->request->get['module_id'] ?? 0);

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/opencart_filter/module/attribute_filter', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
            ]
        ];

        $module_info = $module_id ? $this->model_setting_module->getModule($module_id) : [];
        $settings = array_merge([
            'name' => $this->language->get('heading_title'),
            'attribute_ids' => [],
            'status' => 1,
            'auto_index' => 1,
            'show_counts' => 1,
            'limit' => 24
        ], $module_info);

        $data['save'] = $this->url->link('extension/opencart_filter/module/attribute_filter.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
        $data['rebuild'] = $this->url->link('extension/opencart_filter/module/attribute_filter.rebuild', 'user_token=' . $this->session->data['user_token']);
        $data['module_id'] = $module_id;
        $data['name'] = $settings['name'];
        $data['attribute_ids'] = array_map('intval', $settings['attribute_ids'] ?? []);
        $data['status'] = (int)$settings['status'];
        $data['auto_index'] = (int)$settings['auto_index'];
        $data['show_counts'] = (int)$settings['show_counts'];
        $data['limit'] = (int)$settings['limit'];
        $data['attributes'] = $this->model_extension_opencart_filter_module_attribute_filter->getAttributes();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/opencart_filter/module/attribute_filter', $data));
    }

    public function save(): void {
        $this->load->language('extension/opencart_filter/module/attribute_filter');
        $json = [];

        if ($this->validate()) {
            $this->load->model('setting/module');
            $this->load->model('extension/opencart_filter/module/attribute_filter');

            $module_id = (int)($this->request->post['module_id'] ?? $this->request->get['module_id'] ?? 0);
            $data = $this->request->post;
            $data['attribute_ids'] = array_map('intval', $data['attribute_ids'] ?? []);
            $data['limit'] = max(1, (int)($data['limit'] ?? 24));
            $data['status'] = (int)($data['status'] ?? 0);
            $data['auto_index'] = (int)($data['auto_index'] ?? 0);
            $data['show_counts'] = (int)($data['show_counts'] ?? 0);
            unset($data['module_id']);

            if ($module_id) {
                $this->model_setting_module->editModule($module_id, $data);
            } else {
                $json['module_id'] = $this->model_setting_module->addModule('opencart_filter.attribute_filter', $data);
            }

            if (!empty($data['auto_index'])) {
                $this->model_extension_opencart_filter_module_attribute_filter->registerEvents();
            } else {
                $this->model_extension_opencart_filter_module_attribute_filter->unregisterEvents();
            }

            $this->model_extension_opencart_filter_module_attribute_filter->rebuildIndex();
            $json['success'] = $this->language->get('text_success');
        } else {
            $json['error'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void {
        if ($this->user->hasPermission('modify', 'extension/opencart_filter/module/attribute_filter')) {
            $this->load->model('extension/opencart_filter/module/attribute_filter');
            $this->model_extension_opencart_filter_module_attribute_filter->install();
        }
    }

    public function uninstall(): void {
        if ($this->user->hasPermission('modify', 'extension/opencart_filter/module/attribute_filter')) {
            $this->load->model('extension/opencart_filter/module/attribute_filter');
            $this->model_extension_opencart_filter_module_attribute_filter->uninstall();
        }
    }

    public function rebuild(): void {
        $this->load->language('extension/opencart_filter/module/attribute_filter');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/opencart_filter/module/attribute_filter')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('extension/opencart_filter/module/attribute_filter');
            $this->model_extension_opencart_filter_module_attribute_filter->rebuildIndex();
            $json['success'] = $this->language->get('text_rebuild_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function reindexEvent(string &$route = '', array &$args = [], mixed &$output = null): void {
        $this->load->model('extension/opencart_filter/module/attribute_filter');
        $this->model_extension_opencart_filter_module_attribute_filter->rebuildIndex();
    }

    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/opencart_filter/module/attribute_filter')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((oc_strlen($this->request->post['name'] ?? '') < 3) || (oc_strlen($this->request->post['name'] ?? '') > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if ((int)($this->request->post['limit'] ?? 0) < 1) {
            $this->error['limit'] = $this->language->get('error_limit');
        }

        return !$this->error;
    }
}
