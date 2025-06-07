<?php
namespace JPos;

class JPosCatalog extends \controller {
    private bool $ssl = true;
    private string $JExtensionPagePath = 'extension/extension';
    private string $JExtensionPath = 'extension/';
    public ?\Jpos\PosUser $user = null;
    public ?\Jpos\Jposcart $jposcart = null;

    public function __construct(\Registry $registry) {
        parent::__construct($registry);
        
        $registry->get('session')->data['jpos_customer_id'] ??= 0;
        
        $registry->set('jpostax', new \Jpos\Jpostax($registry));
        
        if (isset($this->session->data['jpos_shipping_address'])) {
            $this->jpostax->setShippingAddress(
                (int)$this->session->data['jpos_shipping_address']['country_id'],
                (int)$this->session->data['jpos_shipping_address']['zone_id']
            );
        } elseif ($this->config->get('config_tax_default') === 'shipping') {
            $this->jpostax->setShippingAddress(
                (int)$this->config->get('config_country_id'),
                (int)$this->config->get('config_zone_id')
            );
        }
        
        if (isset($this->session->data['jpos_payment_address'])) {
            $this->jpostax->setPaymentAddress(
                (int)$this->session->data['jpos_payment_address']['country_id'],
                (int)$this->session->data['jpos_payment_address']['zone_id']
            );
        } elseif ($this->config->get('config_tax_default') === 'payment') {
            $this->jpostax->setPaymentAddress(
                (int)$this->config->get('config_country_id'),
                (int)$this->config->get('config_zone_id')
            );
        }
        
        $this->jpostax->setStoreAddress((int)$this->config->get('config_country_id'), (int)$this->config->get('config_zone_id'));
        
        $this->user ??= new \Jpos\PosUser($registry);
        $this->jposcart ??= new \Jpos\Jposcart($registry);
        
        $this->ssl = version_compare(VERSION, '2.2.0.0', '<') ? 'ssl' : true;
        
        $this->JExtensionPagePath = match(true) {
            version_compare(VERSION, '3.0.0.0', '>=') => 'marketplace/extension',
            default => 'extension/extension',
        };
        
        $registry->get('load')->language('jpos/jpos_global');
    }

    public function refreshUser(): void {
        $this->user = new \Jpos\PosUser($this->registry);
    }

    public function SSL(): bool {
        return $this->ssl;
    }

    public function geAdminExtensionPagePath(): string {
        return $this->JExtensionPagePath;
    }

    public function geAdminExtensionPath(): string {
        return $this->JExtensionPath;
    }

    public function getNameLetters(string $name, bool $space = false, int $chars = 2): string {
        $names = explode(" ", $name);
        $str = '';
        $i = 1;
        foreach ($names as $value) {
            if ($chars) {
                if ($i <= $chars) {
                    $str .= $value[0] . ($space ? " " : "");
                }
            } else {
                $str .= $value[0] . ($space ? " " : "");
            }
            $i++;
        }
        return rtrim($str);
    }

    public function renderJsonOutput(string $path, array &$data): array {
        return ['html' => $this->loadView($path, $data), 'data' => $data];
    }

    public function loadView(string $path, array &$data, bool $twig = false): string {
        if (version_compare(VERSION, '3.0.0.0', '>=') && !$twig) {
            $old_template = $this->config->get('template_engine');
            $this->config->set('template_engine', 'template');
        }

        if (version_compare(VERSION, '2.2.0.0', '<')) {
            $template_path = DIR_TEMPLATE . $this->config->get('config_template') . '/template/' . $this->viewPath($path);
            $view = is_file($template_path)
                ? $this->load->view($this->config->get('config_template') . '/template/' . $this->viewPath($path), $data)
                : $this->load->view('default/template/' . $this->viewPath($path), $data);
        } else {
            $view = $this->load->view($this->viewPath($path), $data);
        }

        if (version_compare(VERSION, '3.0.0.0', '>=') && !$twig) {
            $this->config->set('template_engine', $old_template);
        }

        return $view;
    }

    public function viewPath(string $path): string {
        $path_info = pathinfo($path);
        $npath = $path_info['dirname'] . '/' . $path_info['filename'];
        return version_compare(VERSION, '2.3.0.2', '<=') ? $npath . '.tpl' : $npath;
    }
}