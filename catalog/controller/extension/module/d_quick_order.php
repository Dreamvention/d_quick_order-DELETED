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
            $data['config'] = $this->setting['d_quick_order_setting'];


            $this->load->model('localisation/language');
            $data['languages'] = $this->model_localisation_language->getLanguages();
            foreach ($data['languages'] as $key => $language) {
                if (VERSION >= '2.2.0.0') {
                    $data['languages'][$key]['flag'] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
                } else {
                    $data['languages'][$key]['flag'] = 'view/image/flags/' . $language['image'];
                }
            }

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

//                $this->validateOpenCartRequirements();

                $product_id = (int)$this->request->post['product_id'];
                $product = $this->getProductInfo($product_id);

                $orderId = $this->createOrder($this->request->post);

                if ($orderId) {
                    $productToOrderId = $this->productToOrder($product, $orderId, (int)$this->request->post['amount']);

                    if (!$productToOrderId) {
                        $json['error'] = $this->error;
                    }

                    $json['success'] = sprintf($this->language->get('d_quick_order_success_submit'), $orderId);
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

    public function AddToCartQuickOrderCartAjax()
    {
        $this->load->model('setting/setting');
        $status = $this->model_setting_setting->getSettingValue($this->codename . '_status');

        if ($status) {
            $json = array();
            $this->load->language('extension/module/' . $this->codename);
            $this->load->language('checkout/cart');
            $this->load->model('catalog/product');

            $product_info = $this->model_catalog_product->getProduct((int)$this->request->post['product_id']);
            if ($product_info) {

                //product check price
                $qtyMinStatus = $this->validateMinQtyRequirements($product_info, (int)$this->request->post['quantity']);
                $qtyMaxStatus = $this->validateMaxQtyRequirements($product_info, (int)$this->request->post['quantity']);

                if (!$qtyMinStatus) {
                    $json['text_info'] =
                        $this->language->get('d_quick_order_error_incorrect_min_qty') .
                        $this->language->get('d_quick_order_error_incorrect_min_qty_normal') .
                        $product_info['minimum'];
                }

                if (!$qtyMaxStatus) {
                    $json['text_info'] =
                        $this->language->get('d_quick_order_error_incorrect_max_qty') .
                        $this->language->get('d_quick_order_error_incorrect_max_qty_normal') .
                        $product_info['quantity'];
                }

                $total = strval($this->getTotalSum($product_info));
                $total = $this->currency->format($total, $this->session->data['currency']);

                if (!$total) {
                    $json['error'] = $this->language->get('d_quick_order_error_general');
                } else {
                    $json['product_image'] = $product_info['image'];
                    $json['product_name'] = $product_info['name'];
                    $json['product_model'] = $product_info['model'];
                    $json['product_price'] = $product_info['price'];
                    $json['product_quantity'] = $product_info['quantity'];
                    $json['product_total_price'] = $total;
                }
            } else {
                $json['error'] = $this->language->get('d_quick_order_error_incorrect_product_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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

    public
    function cartValidate($product_id)
    {
        return true;
    }

    public function getTotalSum($product_info)
    {

//                get tax rule
        $taxRule = $this->model_extension_module_d_quick_order->getTaxRule($product_info['tax_class_id']);

//                 oc_tax_rate
        $tax_rate = $this->model_extension_module_d_quick_order->getTaxRate($taxRule['tax_rate_id']);

//                $subtotal =

        $total = (floatval($product_info['price']) * (int)$this->request->post['quantity']);

        return $total;
    }

    public
    function getTotalSumByProductId($product_info)
    {
        if (isset($this->request->post['quantity'])) {
            $quantity = (int)$this->request->post['quantity'];
        } else {
            $quantity = 1;
        }

        if (isset($this->request->post['option'])) {
            $option = array_filter($this->request->post['option']);
        } else {
            $option = array();
        }

        if (isset($this->request->post['recurring_id'])) {
            $recurring_id = $this->request->post['recurring_id'];
        } else {
            $recurring_id = 0;
        }

        $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

        if ($recurrings) {
            $recurring_ids = array();

            foreach ($recurrings as $recurring) {
                $recurring_ids[] = $recurring['recurring_id'];
            }

            if (!in_array($recurring_id, $recurring_ids)) {
                $json['error']['recurring'] = $this->language->get('error_recurring_required');
            }
        }

        if (!isset($json)) {
            $this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id);
            $lastId = $this->db->getLastId();

            // Unset all shipping and payment methods
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);

            // Totals
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

            $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));

//            $json['productTotal'] = $this->currency->format($total, $this->session->data['currency']);
//                   remove cart item
            $this->cart->remove($lastId);
            unset($this->session->data['vouchers'][$lastId]);

        } else {
            $json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']));
        }

        return $json;
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
            $data['custom_field'] = [''];
        } elseif (isset($this->session->data['guest'])) {
            $data['customer_id'] = 0;
            $data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
            $data['firstname'] = $request['name'] ? $request['name'] : $this->session->data['guest']['firstname'];
            $data['lastname'] = $request['name'] ? $request['name'] : $this->session->data['guest']['lastname'];
            $data['email'] = $request['email'] ? $request['email'] : $this->session->data['guest']['email'];
            $data['telephone'] = $request['phone'] ? $request['phone'] : $this->session->data['guest']['telephone'];
            $data['fax'] = '';
            $data['custom_field'] = $this->session->data['guest']['custom_field'] ? $this->session->data['guest']['custom_field'] : "[]";
        } else {
            $data['customer_id'] = 0;
            $data['customer_group_id'] = isset($this->session->data['guest']['customer_group_id']) ? $this->session->data['guest']['customer_group_id'] : 1;
            $data['firstname'] = isset($this->session->data['guest']['firstname']) ? $this->session->data['guest']['firstname'] : "";
            $data['lastname'] = isset($this->session->data['guest']['lastname']) ? $this->session->data['guest']['lastname'] : "";
            $data['email'] = isset($this->session->data['guest']['email']) ? $this->session->data['guest']['email'] : "";
            $data['fax'] = '';
            $data['custom_field'] = isset($this->session->data['guest']['custom_field']) ? $this->session->data['guest']['custom_field'] : "[]";
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
        $data['payment_custom_field'] = "[]";
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
        $data['shipping_custom_field'] = '[]';
        $data['shipping_method'] = "";
        $data['shipping_code'] = "";

        $data['comment'] = $request['comment'] ? $request['comment'] : $this->session->data['guest']['telephone'];
        $data['total'] = $request['amount'] ? $request['amount'] : 1;


//       Set Config Status Order Id
        $statuses = $this->config->get($this->codename . '_statuses');

        $data['order_status_id'] = $statuses['pending']['order_status_id'];
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
    function productToOrder($product, $orderid, $qty)
    {
        $data['order_id'] = $orderid;
        $data['product_id'] = $product['product_id'];
        $data['name'] = $product['name'];
        $data['model'] = $product['model'];
        $data['quantity'] = $qty;
        $data['price'] = $product['price'];
        $data['total'] = $product['price'] * (int)$qty;
        $data['tax'] = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
        $data['reward'] = "0";

        $this->load->model('extension/module/d_quick_order');
        return $this->model_extension_module_d_quick_order->productToOrder($data);
    }

    public
    function dd($data)
    {
        highlight_string("<?php\n\$data =\n" . var_export($data, true) . ";\n?>");
        die();
    }
}