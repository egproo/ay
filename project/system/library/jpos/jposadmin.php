<?php
namespace JPos;

class JPosAdmin extends \controller {
    public string $JocToken = 'token';
    private bool $JocSSL = true;
    private string $JExtensionPagePath = 'extension/extension';
    private string $JExtensionPath = 'extension/';

    public function __construct($registry) {
        parent::__construct($registry);
        if (version_compare(VERSION, '2.2.0.0', '<')) {
            $this->JocSSL = true;
        }
        if (version_compare(VERSION, '3.0.0.0', '>=')) {
            $this->JocToken = 'user_token';
            $this->JExtensionPagePath = 'marketplace/extension';
        }
    }

    public function getAdminToken(): string {
        return $this->JocToken;
    }

    public function geUrlSSL(): bool {
        return $this->JocSSL;
    }

    public function geAdminExtensionPagePath(): string {
        return $this->JExtensionPagePath;
    }

    public function geAdminExtensionPath(): string {
        return $this->JExtensionPath;
    }

    public function var_summernote(): string {
        return '';
    }

    public function getStores(array $options = []): array {
        $this->load->model('setting/store');
        $stores_ = $this->model_setting_store->getStores();
        $stores = [
            0 => [
                'name' => $this->language->get('text_default'),
                'store_id' => '0',
                'url' => HTTP_CATALOG,
                'ssl' => HTTPS_CATALOG
            ]
        ];

        foreach ($options as $key => $value) {
            foreach ($value['find'] as $find => $short_code) {
                $value['str'] = str_replace($short_code, $stores[0][$find] ?? '', $value['str']);
            }
            $stores[0][$key] = $value['str'];
        }

        foreach ($stores_ as $store) {
            foreach ($options as $key => $value) {
                foreach ($value['find'] as $find => $short_code) {
                    $value['str'] = str_replace($short_code, $store[$find] ?? '', $value['str']);
                }
                $store[$key] = $value['str'];
            }
            $stores[$store['store_id']] = $store;
        }

        return $stores;
    }

    public function getCustomerGroups(): array {
        $this->load->model('customer/customer_group');
        return $this->model_customer_customer_group->getCustomerGroups();
    }

    public function getLanguages(): array {
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        foreach ($languages as &$language) {
            if (version_compare(VERSION, '2.2.0.0', '>=')) {
                $language['lang_flag'] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
            } else {
                $language['lang_flag'] = 'view/image/flags/' . $language['image'];
            }
        }

        return $languages;
    }

    public function loadView(string $path, array &$data, bool $twig = false): string {
        if (version_compare(VERSION, '3.0.0.0', '>=') && !$twig) {
            $old_template = $this->config->get('template_engine');
            $this->config->set('template_engine', 'template');
        }

        $view = $this->load->view($this->viewPath($path), $data);

        if (version_compare(VERSION, '3.0.0.0', '>=') && !$twig) {
            $this->config->set('template_engine', $old_template);
        }

        return $view;
    }

    public function viewPath(string $path): string {
        $path_info = pathinfo($path);
        $npath = $path_info['dirname'] . '/' . $path_info['filename'];

        if (version_compare(VERSION, '2.3.0.2', '<=')) {
            $npath .= '.tpl';
        }

        return $npath;
    }

    public function buildJposTables(): void {
        // Implementation not provided in the original code
    }
}