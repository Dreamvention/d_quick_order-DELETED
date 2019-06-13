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
    private $errorValidationCart;
    private $selector;
    private $codename = 'd_quick_order';
    private $route = 'extension/module/d_quick_order';
    private $setting;
    private $orderProductOptions;

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

        $status = $this->config->get($this->codename . '_status');

        if ($status) {
            $this->load->language($this->route);
            $this->load->config($this->codename);

            $this->setting = $this->model_setting_setting->getSetting($this->codename);
            $data['config'] = $this->setting[$this->codename . '_setting'];

            $this->load->model('localisation/language');
            $data['languages'] = $this->model_localisation_language->getLanguages();
            foreach ($data['languages'] as $key => $language) {
                if (VERSION >= '2.2.0.0') {
                    $data['languages'][$key]['flag'] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
                } else {
                    $data['languages'][$key]['flag'] = 'view/image/flags/' . $language['image'];
                }
            }

            $settingSelector = $this->setting['d_quick_order_setting']['selector'];
            if ($settingSelector && !empty($settingSelector)) {
                $this->selector = $settingSelector;
            } else {
                $this->selector = $data['config']['selector'];
            }

            $html = $this->load->view('extension/module/' . $this->codename, $data);

            $html_dom = new d_simple_html_dom();
            $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

            $findSelector = $html_dom->find($this->selector, 0);

            if ($findSelector) {
                $html_dom->find($this->selector, 0)->outertext .= $html;
            }

            $output = (string)$html_dom;
        }
    }

    public function AddToCartQuickOrderCartAjax()
    {
        $this->load->model('setting/setting');
        $status = $this->config->get($this->codename . '_status');

        if ($status) {
            $json = array();
            $this->load->language('extension/module/' . $this->codename);
            $this->load->model('catalog/product');

            $product_info = $this->model_catalog_product->getProduct((int)$this->request->post['product_id']);
            if ($product_info) {
//                $result = $this->getTotalSum($product_info, (int)$this->request->post['quantity']);
                $result = $this->getTotalSumByCart($product_info);

                $total = $result['total'];
                $totalToView = $this->currency->format($total, $this->session->data['currency']);

                $json['product_image'] = $product_info['image'];
                $json['product_name'] = $product_info['name'];
                $json['product_model'] = $product_info['model'];
                $json['product_price'] = $product_info['price'];
                $json['product_quantity'] = $product_info['quantity'];
                $json['product_total_price'] = $totalToView;
                $json['success'] = true;
            } else {
                $json['error'] = $this->language->get('d_quick_order_error_incorrect_product_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addOrderAjax()
    {
        $this->load->model('setting/setting');
        $status = $this->config->get($this->codename . '_status');

        if ($status) {
            $this->load->language('extension/module/' . $this->codename);
            $json = array();

            if ($this->validateAjaxOrder($this->request->post)) {
                $this->load->model('extension/module/d_quick_order');
                $this->load->model('catalog/product');

                $product_info = $this->model_catalog_product->getProduct((int)$this->request->post['product_id']);

                if ($this->cartValidate($product_info)) {
//                  Get totals
//                    $result = $this->getTotalSum($product_info, (int)$this->request->post['quantity']);
                    $result = $this->getTotalSumByCart($product_info);

                    $totalSum = $result['total'];
                    $totalTax = 0;
                    foreach ($result['tax'] as $key => $tax) {
                        $totalTax += $tax;
                    }

                    // Chk user buy something and get orderId
                    $orderId = $this->getOrderIdToPreorder($this->request->post['telephone']);

//                  Chk product in cart
                    $buyProduct = $this->model_extension_module_d_quick_order->getProductById($this->request->post['product_id']);
                    if ($buyProduct) {
                        $qty = (int)$buyProduct['quantity'] + (int)$this->request->post['quantity'];
                        $tax = number_format($buyProduct['tax'] + $totalTax, 2);
                        $total = $buyProduct['price'] * $qty;

                        $product_to_order_id = $product_to_order_id = $this->productToOrderUpdate($product_info, $orderId, $qty, $total, $tax);
                    } else {
                        $product_to_order_id = $this->productToOrder($product_info, $orderId, (int)$this->request->post['quantity'], $totalSum, $totalTax);
                    }

//                  Chk product option
                    if (isset($this->request->post['option'])) {
                        $this->createOptionsToOrder($this->request->post['option'], $orderId, $product_to_order_id);
                    }

//                  Save User details to Session
                    $this->persistData($this->request->post);

                    $json['success'] = sprintf($this->language->get('d_quick_order_success_submit'), $orderId);

                } else {
                    $json['success'] = true;
                    $json['warning'] = $this->errorValidationCart;
                }

            } else {
                $json['error'] = $this->error;
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }


    public
    function validateAjaxOrder($data)
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
                        if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                            break;
                        } else {
                            $this->error = $this->language->get('d_quick_order_error_email');
                        }
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

    public
    function cartValidate($product_info)
    {
        $this->load->language('extension/module/d_quick_order');

        $option = array();
        if (isset($this->request->post['option'])) {
            $option = array_filter($this->request->post['option']);
        }

        $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);
        foreach ($product_options as $product_option) {
            if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                $this->errorValidationCart['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
            }
        }

        $qtyMinStatus = $this->validateMinQtyRequirements($product_info, (int)$this->request->post['quantity']);

        $qtyMaxStatus = $this->validateMaxQtyRequirements($product_info, (int)$this->request->post['quantity']);

        //product check price

        if (!$qtyMinStatus) {
            $this->errorValidationCart =
                $this->language->get('d_quick_order_error_incorrect_min_qty') . " " .
                $this->language->get('d_quick_order_error_incorrect_min_qty_normal') . $product_info['minimum'];
        }

        if (!$qtyMaxStatus) {
            $this->errorValidationCart =
                $this->language->get('d_quick_order_error_incorrect_max_qty') .
                $this->language->get('d_quick_order_error_incorrect_max_qty_normal') . $product_info['quantity'];
        }

        return !$this->errorValidationCart;
    }

    public function getTotalSum($product, $quantity)
    {
        $total = 0;

        $total += $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $quantity;

        $tax_data = array();

        if ($product['tax_class_id']) {
            $tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);

            foreach ($tax_rates as $tax_rate) {
                if (!isset($tax_data[$tax_rate['tax_rate_id']])) {
                    $tax_data[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $quantity);
                } else {
                    $tax_data[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $quantity);
                }
            }
        }

        return array(
            'total' => $total,
            'tax' => $tax_data
        );
    }

    public function getTotalSumByCart($product_info)
    {
        $this->load->model('tool/upload');

        // Save and store current cart
        $tempProducts = $this->cart->getProducts();

        if (VERSION >= '3.0.0.0' || VERSION == '2.1.0.2') {
            foreach ($tempProducts as $key => $tempProduct) {
                $this->cart->remove(isset($tempProduct['cart_id']) ? $tempProduct['cart_id'] : null);
            }
        }
        else {
            foreach ($tempProducts as $key => $tempProduct) {
                $this->cart->remove($key);
            }
        }

        if (isset($this->request->post['option'])) {
            $option = array_filter($this->request->post['option']);
        } else {
            $option = array();
        }

        $this->cart->add($product_info['product_id'], $this->request->post['quantity'], $option);

        if (VERSION >= '3.0.0.0' || VERSION == '2.1.0.2') {
            $myProductCartId = $this->db->getLastId();
        } else {
            $myProductCartId = key($this->cart->getProducts());
        }

        $option_data = array();
        foreach ($this->cart->getProducts() as $product) {
            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id' => $option['option_id'],
                    'option_value_id' => $option['option_value_id'],
                    'name' => $option['name'],
                    'value' => $option['value'],
                    'type' => $option['type']
                );
            }
        }

        $this->orderProductOptions = $option_data ? $option_data : null;

        // Totals
        if (VERSION >= '3.0.0.0') {
            $this->load->model('setting/extension');

            $totals = array();
            $taxes = $this->cart->getTaxes();
            $total = 0;

            // Because __call can not keep var references so we put them into an array.
            $total_data = array(
                'totals' => &$totals,
                'taxes' => &$taxes,
                'total' => &$total
            );

            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = array();

                $results = $this->model_setting_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get('total_' . $result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);

                        // We have to put the totals in an array so that they pass by reference.
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
                }

                $sort_order = array();

                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $totals);
            }
        } else {
            $this->load->model('extension/extension');

            $total_data = array();
            $total = 0;
            $taxes = $this->cart->getTaxes();

            // Display prices
            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $sort_order = array();

                $results = $this->model_extension_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('total/' . $result['code']);

                        $this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
                    }
                }

                $sort_order = array();

                foreach ($total_data as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $total_data);
            }
        }


        // Delete my custom product and add temp to cart then clean session
        $this->cart->remove($myProductCartId);

        foreach ($tempProducts as $tempProduct) {
            $this->cart->add($tempProduct['product_id'], $tempProduct['quantity'], $tempProduct['option'], $tempProduct['recurring']);
        }

        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);

        $return['total'] = $total;
        $return['tax'] = $taxes;

        return $return;
    }

    public function getOrderIdToPreorder($phone)
    {
        $order = $this->model_extension_module_d_quick_order->getOrderByTelephone($phone);
        if ($order) {
            $orderId = $order['quick_order_id'];
        } else {
            $orderId = $this->createOrder($this->request->post);
        }

        return $orderId;
    }

    public function createOptionsToOrder($postOptions, $orderId, $product_to_order_id)
    {
        $this->load->model('extension/module/d_quick_order');
        $this->load->model('catalog/product');
        $this->load->model('tool/upload');

        $this->model_extension_module_d_quick_order->deleteOptionsByProductIdAndOrderId($product_to_order_id, $orderId);

        $option_data = array();

        $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);
        foreach ($postOptions as $key => $postOption) {
            if (is_array($postOption)) {
                foreach ($postOption as $item) {
                    foreach ($product_options as $product_option) {
                        if ($key == $product_option['product_option_id']) {
                            if (($product_option['product_option_value'])) {
                                foreach ($product_option['product_option_value'] as $option_value) {
                                    if ($item == $option_value['product_option_value_id']) {
                                        if ($product_option['type'] != 'file') {
                                            $value = $option_value['name'];
                                        } else {
                                            $upload_info = $this->model_tool_upload->getUploadByCode($product_option['value']);
                                            if ($upload_info) {
                                                $value = $upload_info['name'];
                                            } else {
                                                $value = '';
                                            }
                                        }
                                        $product_option_value_id = $option_value['product_option_value_id'];
                                    }
                                }
                            } else {
                                $value = $postOption;
                                $product_option_value_id = (int)$item;
                            }
                            $option_data['options'][] = array(
                                'quick_order_id' => (int)$orderId,
                                'product_to_order_id' => (int)$product_to_order_id,
                                'product_option_id' => $product_option['product_option_id'],
                                'product_option_value_id' => (int)$product_option_value_id,
                                'name' => $product_option['name'],
                                'value' => $value,
                                'type' => $product_option['type']
                            );
                        }
                    }
                }
            }else{
                foreach ($product_options as $product_option) {
                    if ($key == $product_option['product_option_id']) {
                        if (($product_option['product_option_value'])) {
                            foreach ($product_option['product_option_value'] as $option_value) {
                                if ($postOption == $option_value['product_option_value_id']) {
                                    if ($product_option['type'] != 'file') {
                                        $value = $option_value['name'];
                                    } else {
                                        $this->load->model('tool/upload');
                                        $upload_info = $this->model_tool_upload->getUploadByCode($product_option['value']);
                                        if ($upload_info) {
                                            $value = $upload_info['name'];
                                        } else {
                                            $value = '';
                                        }
                                    }
                                    $product_option_value_id = $option_value['product_option_value_id'];
                                }
                            }
                        } else {
                            $value = $postOption;
                            $product_option_value_id = 0;
                        }
                        $option_data['options'][] = array(
                            'quick_order_id' => (int)$orderId,
                            'product_to_order_id' => (int)$product_to_order_id,
                            'product_option_id' => $product_option['product_option_id'],
                            'product_option_value_id' => (int)$product_option_value_id,
                            'name' => $product_option['name'],
                            'value' => $value,
                            'type' => $product_option['type']
                        );
                    }
                }
            }
        }
        foreach ($option_data['options'] as $option) {
            $this->model_extension_module_d_quick_order->createOptionsByProductIdAndOrderId($option);
        }
    }

    public
    function validateMinQtyRequirements($product, $quantity)
    {
        if ((int)$quantity < (int)$product['minimum']) {
            return false;
        } else {
            return true;
        }
    }

    public
    function validateMaxQtyRequirements($product, $quantity)
    {
        if ((int)$product['quantity'] < (int)$quantity) {
            return false;
        } else {
            return true;
        }
    }

    public
    function getProductInfo($id)
    {
        $this->load->model('catalog/product');

        return $this->model_catalog_product->getProduct($id);
    }

    public function checkUserBuySomething($telephone)
    {
        return $this->model_extension_module_d_quick_order->getOrderByTelephone($telephone);
    }

    public function checkUserBuyProduct($product_id)
    {
        return $this->model_extension_module_d_quick_order->getProductById($product_id);
    }

    public
    function createOrder($request)
    {
        $this->load->model('account/custom_field');
        $this->load->model('extension/module/d_quick_order');

        $this->load->language('checkout/checkout');
        $order_data['store_id'] = $this->config->get('config_store_id');
        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTPS_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }
        }

        $data['invoice_no'] = 0;
        $data['invoice_prefix'] = $this->config->get('config_invoice_prefix') ? $this->config->get('config_invoice_prefix') : "";

        $data['store_id'] = $order_data['store_id'];
        $data['store_name'] = $this->config->get('config_name');
        $data['store_url'] = $order_data['store_url'];

        $this->load->model('account/customer');
        if ($this->customer->isLogged()) {
            $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
            $data['customer_id'] = $this->customer->getId();
            $data['customer_group_id'] = $customer_info['customer_group_id'];
            $data['firstname'] = $customer_info['firstname'];
            $data['lastname'] = $customer_info['lastname'];
            $data['email'] = $customer_info['email'];
            $data['telephone'] = $customer_info['telephone'];
            $data['fax'] = $customer_info['fax'];
            $data['custom_field'] = array();
        } elseif (isset($this->session->data['guest'])) {
            $data['customer_id'] = 0;
            $data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
            $data['firstname'] = $this->session->data['guest']['firstname'];
            $data['lastname'] = $this->session->data['guest']['lastname'];
            $data['email'] = $this->session->data['guest']['email'];
            $data['telephone'] = $this->session->data['guest']['telephone'];
            $data['fax'] = '';
            $data['custom_field'] = array();
        } else {
            $data['customer_id'] = 0;
            $data['customer_group_id'] = isset($this->session->data['guest']['customer_group_id']) ? $this->session->data['guest']['customer_group_id'] : 1;
            $data['firstname'] = isset($this->session->data['guest']['firstname']) ? $this->session->data['guest']['firstname'] : "";
            $data['lastname'] = isset($this->session->data['guest']['lastname']) ? $this->session->data['guest']['lastname'] : "";
            $data['email'] = isset($this->session->data['guest']['email']) ? $this->session->data['guest']['email'] : "";
            $data['fax'] = '';
            $data['custom_field'] = array();
        }


        if ($request['firstname']) {
            $data['firstname'] = $request['firstname'];
        } else {
            $data['firstname'] = "";
        }

        if ($request['email']) {
            $data['email'] = $request['email'];
        } else {
            $data['email'] = "";
        }

        if ($request['telephone']) {
            $data['telephone'] = $request['telephone'];
        } else {
            $data['telephone'] = "";
        }

        $data['payment_firstname'] = '';
        $data['payment_lastname'] = '';
        $data['payment_company'] = '';

        $data['payment_address_1'] = isset($this->session->data['shipping_address']['address_1']) ? $this->session->data['shipping_address']['address_1'] : '';
        $data['payment_address_2'] = isset($this->session->data['shipping_address']['address_2']) ? $this->session->data['shipping_address']['address_2'] : '';
        $data['payment_city'] = isset($this->session->data['shipping_address']['city']) ? $this->session->data['shipping_address']['city'] : '';
        $data['payment_postcode'] = '';
        $data['payment_zone'] = '';
        $data['payment_zone_id'] = isset($this->session->data['payment_address']['zone_id']) ? $this->session->data['payment_address']['zone_id'] : '';
        $data['payment_country'] = isset($this->session->data['shipping_address']['city']) ? $this->session->data['shipping_address']['city'] : '';
        $data['payment_country_id'] = isset($this->session->data['shipping_address']['country_id']) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id');
        $data['payment_address_format'] = '';
        $data['payment_custom_field'] = array();
        $data['payment_method'] = '';
        $data['payment_code'] = 'cod';


        $data['shipping_firstname'] = isset($this->session->data['shipping_address']['firstname']) ? $this->session->data['shipping_address']['firstname'] : '';
        $data['shipping_lastname'] = isset($this->session->data['shipping_address']['lastname']) ? $this->session->data['shipping_address']['lastname'] : '';
        $data['shipping_company'] = isset($this->session->data['shipping_address']['company']) ? $this->session->data['shipping_address']['company'] : '';
        $data['shipping_address_1'] = isset($this->session->data['shipping_address']['address_1']) ? $this->session->data['shipping_address']['address_1'] : '';
        $data['shipping_address_2'] = isset($this->session->data['shipping_address']['address_2']) ? $this->session->data['shipping_address']['address_2'] : '';
        $data['shipping_postcode'] = isset($this->session->data['shipping_address']['postcode']) ? $this->session->data['shipping_address']['postcode'] : '';
        $data['shipping_city'] = isset($this->session->data['shipping_address']['city']) ? $this->session->data['shipping_address']['city'] : '';

        $data['shipping_country'] = "";
        $data['shipping_country_id'] = isset($this->session->data['shipping_address']['country_id']) ? $this->session->data['shipping_address']['country_id'] : 0;
        $data['shipping_zone'] = '';
        $data['shipping_zone_id'] = isset($this->session->data['shipping_address']['zone_id']) ? $this->session->data['shipping_address']['zone_id'] : 0;
        $data['shipping_zone_code'] = "";
        $data['shipping_address_format'] = '';
        $data['shipping_custom_field'] = array();
        $data['shipping_method'] = "";
        $data['shipping_code'] = "";

        $data['comment'] = $request['comment'] ? $request['comment'] : $this->session->data['guest']['telephone'];
        $data['total'] = $request['quantity'] ? $request['quantity'] : 1;


//       Set Config Status Order Id
        $statuses = $this->config->get($this->codename . '_statuses');

        $data['order_status_id'] = $statuses[1]['order_status_id'];
        if (isset($this->request->cookie['tracking'])) {
            $order_data['tracking'] = $this->request->cookie['tracking'];

            $subtotal = $this->cart->getSubTotal();

            // Affiliate
            $affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

            if ($affiliate_info) {
                $order_data['affiliate_id'] = $affiliate_info['customer_id'];
                $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
            } else {
                $order_data['affiliate_id'] = 0;
                $order_data['commission'] = 0;
            }

            // Marketing
            $this->load->model('checkout/marketing');
            $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

            if ($marketing_info) {
                $order_data['marketing_id'] = $marketing_info['marketing_id'];
            } else {
                $order_data['marketing_id'] = 0;
            }
        } else {
            $order_data['affiliate_id'] = 0;
            $order_data['commission'] = 0;
            $order_data['marketing_id'] = 0;
            $order_data['tracking'] = '';
        }

        $data['affiliate_id'] = $order_data['affiliate_id'];
        $data['commission'] = $order_data['commission'];
        $data['marketing_id'] = $order_data['marketing_id'];
        $data['tracking'] = $order_data['tracking'];


        $order_data['language_id'] = $this->config->get('config_language_id');
        $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
        $order_data['currency_code'] = $this->session->data['currency'];
        $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
        $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

        $data['language_id'] = $this->config->get('config_language_id');
        $data['currency_id'] = $this->currency->getId($this->session->data['currency']);
        $data['currency_code'] = $this->session->data['currency'];
        $data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
        $data['ip'] = $this->request->server['REMOTE_ADDR'];


        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
        } else {
            $order_data['forwarded_ip'] = '';
        }

        $data['forwarded_ip'] = $order_data['forwarded_ip'];
        $data['user_agent'] = isset($this->request->server['HTTP_USER_AGENT']) ? $this->request->server['HTTP_USER_AGENT'] : '';
        $data['accept_language'] = isset($this->request->server['HTTP_ACCEPT_LANGUAGE']) ? $this->request->server['HTTP_ACCEPT_LANGUAGE'] : '';

        return $this->model_extension_module_d_quick_order->addOrder($data);
    }

    public
    function productToOrder($product, $orderId, $qty, $total, $tax)
    {
        $data['quick_order_id'] = $orderId;
        $data['product_id'] = $product['product_id'];
        $data['name'] = $product['name'];
        $data['model'] = $product['model'];
        $data['quantity'] = $qty;
        $data['price'] = $product['price'];
        $data['total'] = $total;
        $data['tax'] = $tax;
        $data['reward'] = "0";

        $this->load->model('extension/module/d_quick_order');
        return $this->model_extension_module_d_quick_order->productToOrder($data);
    }

    public
    function productToOrderUpdate($product, $orderid, $qty, $total, $tax)
    {
        $data['quick_order_id'] = $orderid;
        $data['product_id'] = $product['product_id'];
        $data['name'] = $product['name'];
        $data['model'] = $product['model'];
        $data['quantity'] = $qty;
        $data['price'] = $product['price'];
        $data['total'] = $total;
        $data['tax'] = $tax;
        $data['reward'] = "0";

        $this->load->model('extension/module/d_quick_order');
        $this->model_extension_module_d_quick_order->productToOrderUpdate($data);

        $order_to_product_id = $this->model_extension_module_d_quick_order->getProductById($product['product_id']);
        return (int)$order_to_product_id['product_to_order_id'];
    }

    public
    function persistData($data)
    {
        if (isset($data['firstname'])) {
            $this->session->data[$this->codename]['firstname'] = $data['firstname'];
        }

        if (isset($data['email'])) {
            $this->session->data[$this->codename]['email'] = $data['email'];
        }

        if (isset($data['telephone'])) {
            $this->session->data[$this->codename]['telephone'] = $data['telephone'];
        }

        if (isset($data['comment'])) {
            $this->session->data[$this->codename]['comment'] = $data['comment'];
        }

        $this->session->data[$this->codename]['ordered'][(int)$data['product_id']] = true;
    }

    public
    function dd($data)
    {
        highlight_string("<?php\n\$data =\n" . var_export($data, true) . ";\n?>");
        die();
    }

    public
    function dump($data)
    {
        highlight_string("<?php\n\$data =\n" . var_export($data, true) . ";\n?>");
    }
}