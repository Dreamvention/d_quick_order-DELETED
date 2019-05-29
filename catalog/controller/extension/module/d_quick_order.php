<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 22.05.2019
 * Time: 18:38
 */

class ControllerExtensionModuleDQuickOrder extends Controller
{
    private $error = array();
    private $codename = 'd_quick_order';
    private $route = 'extension/module/d_quick_order';
    private $setting;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model($this->route);
        $this->d_opencart_patch = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_opencart_patch.json'));
        if ($this->d_opencart_patch) {
            $this->load->model('extension/d_opencart_patch/load');
        }

        $this->setting = $this->config->get($this->codename . '_setting');
    }

    public function index($setting)
    {
        $this->load->config($this->codename);
        $this->setting = $this->config->get($this->codename . '_setting');
    }

    public function catalog_controller_common_header_before(&$route, &$data)
    {
        $status = $this->config->get($this->codename . '_status');

        if ($status) {
            if (file_exists('catalog/view/theme/default/stylesheet/' . $this->codename . '/' . $this->codename . '.css')) {
                $this->document->addStyle('catalog/view/theme/default/stylesheet/' . $this->codename . '/' . $this->codename . '.css');
            } else {
                $this->document->addStyle('catalog/view/theme/default/stylesheet/' . $this->codename . '.css');
            }
        }
    }

    public function catalog_controller_common_footer_before(&$route, &$data)
    {
        $status = $this->config->get($this->codename . '_status');

        if ($status) {
            if (file_exists('catalog/view/theme/default/javascript/' . $this->codename . '/extra/jquery.maskedinput.min.js')) {
                $this->document->addScript('catalog/view/theme/default/javascript/' . $this->codename . '/extra/jquery.maskedinput.min.js');
            } else {
                $this->document->addScript('catalog/view/theme/default/javascript/extra/jquery.maskedinput.min.js');
            }

            if (file_exists('catalog/view/theme/default/javascript/' . $this->codename . '/' . $this->codename . '.js')) {
                $this->document->addScript('catalog/view/theme/default/javascript/' . $this->codename . '/' . $this->codename . '.js');
            } else {
                $this->document->addScript('catalog/view/theme/default/javascript/' . $this->codename . '.js');
            }
        }
    }

    public function catalog_view_product_product_after(&$route, &$data, &$output)
    {
        $this->load->model('setting/setting');
        $status = $this->model_setting_setting->getSettingValue($this->codename . '_status');

        if ($status) {
            $this->load->language($this->route);
            $this->load->config($this->codename);

            $this->setting = $this->model_setting_setting->getSetting($this->codename);
            $data = $this->setting['d_quick_order_setting'];

            $html = $this->load->view('extension/module/' . $this->codename, $data);

            $html_dom = new d_simple_html_dom();
            $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
            $html_dom->find('#button-cart', 0)->outertext .= $html;
            $output = (string)$html_dom;
        }
    }

    public function addOrderAjax()
    {
        $this->load->model('setting/setting');
        $status = $this->model_setting_setting->getSettingValue($this->codename . '_status');

        if ($status) {
            $json = array();
            if ($this->validateAjaxOrder($this->request->post)) {
                $result = $this->createOrder($this->request->post);

                if ($result) {
                    $lastid = $this->db->getLastId();

                    $json['success'] = sprintf($this->language->get('d_quick_order_success_submit'), $lastid);
                } else {
                    $json['error'] = $this->error;
                }

            } else {
                $json['error'] = $this->error;
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    public function validateAjaxOrder($data)
    {
        $this->load->language('extension/module/d_quick_order');
        $settings = $this->model_setting_setting->getSetting($this->codename);

        foreach ($data as $key => $val) {
            if ($key == "phone" && empty($val)) {
                $this->error = $this->language->get('d_quick_order_error_field') . " " . $key . "!";
            }

            if ($key == "amount" && empty($val)) {
                $this->error = $this->language->get('d_quick_order_error_field') . " " . $key . "!";
            }

            if ($key == "name") {
                if ($settings['d_quick_order_setting']['modal_field']['name']) {
                    if ($settings['d_quick_order_setting']['modal_field']['name_required'] && empty($val)) {
                        $this->error = $this->language->get('d_quick_order_error_field') . " " . $key . "!";
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }

            if ($key == "email") {
                if ($settings['d_quick_order_setting']['modal_field']['email']) {
                    if ($settings['d_quick_order_setting']['modal_field']['email_required'] && empty($val)) {
                        $this->error = $this->language->get('d_quick_order_error_field') . " " . $key . "!";
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }

            if ($key == "comment") {
                if ($settings['d_quick_order_setting']['modal_field']['comment']) {
                    if ($settings['d_quick_order_setting']['modal_field']['comment_required'] && empty($val)) {
                        $this->error = $this->language->get('d_quick_order_error_field') . " " . $key . "!";
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }
        }

        if (!$this->error) {
            return true;
        }

        return false;
    }

    public function getProductInfo($id)
    {
        $this->load->model('catalog/product');

        return $this->model_catalog_product->getProduct($id);
    }

    public function createOrder($request)
    {
        $product_id = (int)$request['d_qo_product_id'];
        $product = $this->getProductInfo($product_id);
        $productLink = $this->url->link('product/product', 'product_id=' . $product_id);
        $user_session_token = $this->session->data['user_token'];

        $data['product_id'] = $request['d_qo_product_id'];
        $data['product_name'] = $product['name'] ? $product['name'] : null;
        $data['product_link'] = $productLink ? $productLink : null;
        $data['product_price'] = $product['price'] ? $product['price'] : null;
        $data['product_amount'] = $request['amount'] ? $request['amount'] : null;
        $data['customer_session_id'] = $user_session_token ? $user_session_token : null;
        $data['customer_name'] = $request['name'] ? $request['name'] : null;
        $data['customer_email'] = $request['email'] ? $request['email'] : null;
        $data['customer_phone'] = $request['phone'] ? $request['phone'] : null;
        $data['customer_comment'] = $request['comment'] ? $request['comment'] : null;
        $data['status'] = 'open';

        $this->load->model('extension/module/d_quick_order');
        return $this->model_extension_module_d_quick_order->store($data);
    }
}