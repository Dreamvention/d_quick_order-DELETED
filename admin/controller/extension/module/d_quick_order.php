<?php

class ControllerExtensionModuleDQuickOrder extends Controller
{
    private $codename = 'd_quick_order';
    private $route = 'extension/module/d_quick_order';
    private $store_id = 0;
    private $filters = array();
    private $extension = array();
    private $error = array();
    private $setting;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->d_opencart_patch = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_opencart_patch.json'));
        $this->d_shopunity = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_shopunity.json'));
        $this->d_twig_manager = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_twig_manager.json'));
        $this->d_event_manager = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_event_manager.json'));
        $this->extension = json_decode(file_get_contents(DIR_SYSTEM . 'library/d_shopunity/extension/' . $this->codename . '.json'), true);
        $this->store_id = (isset($this->request->get['store_id'])) ? $this->request->get['store_id'] : 0;
        $this->load->language($this->route);
    }

    public function index()
    {
        if ($this->d_shopunity) {
            $this->load->model('extension/d_shopunity/mbooth');
            $this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename . '_admin');
        }

        if ($this->d_twig_manager) {
            $this->load->model('extension/module/d_twig_manager');
            $this->model_extension_module_d_twig_manager->installCompatibility();
        }

        if ($this->d_event_manager) {
            $this->load->model('extension/module/d_event_manager');
            $this->model_extension_module_d_event_manager->installCompatibility();
        }

        if (!$this->isSetup()) {
            $this->setupView();
            return;
        }

        //save post
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');

            if (VERSION < '2.3.0.0') {
                if ($this->request->post[$this->codename . '_setting']['skipped_models']) {
                    $this->request->post[$this->codename . '_setting']['skipped_models'] = explode(",", $this->request->post[$this->codename . '_setting']['skipped_models']);
                } else {
                    $this->request->post[$this->codename . '_setting']['skipped_models'] = array();
                }
                if (!in_array('total', $this->request->post[$this->codename . '_setting']['skipped_models'])) {
                    $this->request->post[$this->codename . '_setting']['skipped_models'][] = 'total';
                }
            }

            $this->model_setting_setting->editSetting($this->codename, $this->request->post, $this->store_id);

            $new_setting = array();
            foreach ($this->request->post as $k => $v) {
                $new_setting['module_' . $k] = $v;
            }
            $this->model_setting_setting->editSetting('module_' . $this->codename, $new_setting, $this->store_id);

            $this->load->model('extension/d_opencart_patch/url');
            $json['redirect'] = $this->model_extension_d_opencart_patch_url->getExtensionLink('module');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->language($this->route);
        $this->load->model($this->route);
        $this->load->config($this->codename);
        $this->load->model('setting/setting');
        $this->load->model('extension/d_opencart_patch/setting');
        $this->load->model('extension/d_opencart_patch/modification');
        $this->load->model('extension/d_opencart_patch/load');
        $this->load->model('extension/d_opencart_patch/user');
        $this->load->model('extension/d_opencart_patch/store');
        $this->load->model('extension/d_opencart_patch/url');


        $url_params = array();
        $url = '';

        if (isset($this->response->get['store_id'])) {
            $url_params['store_id'] = $this->store_id;
        }

        if (isset($this->response->get['config'])) {
            $url_params['config'] = $this->response->get['config'];
        }

        //Customer
        if (isset($this->request->get['filter_name'])) {
            $url_params['filter_name'] = urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }


        if (isset($this->request->get['filter_email'])) {
            $url_params['filter_email'] = urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_phone'])) {
            $url_params['filter_phone'] = urlencode(html_entity_decode($this->request->get['filter_phone'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status_id'])) {
            $url_params['filter_order_status_id'] = (int)urlencode(html_entity_decode($this->request->get['filter_order_status_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['sort'])) {
            $url_params['sort'] = $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url_params['order'] = $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url_params['page'] = $this->request->get['page'];
        }

        $url = ((!empty($url_params)) ? '&' : '') . http_build_query($url_params);

        $data = $this->prepareDataToPage($url);

        if (VERSION < '2.3.0.0') {
            $this->load->config('d_event_manager');
        }

        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : null;
        $filter_email = (isset($this->request->get['filter_email'])) ? $this->request->get['filter_email'] : null;
        $filter_phone = (isset($this->request->get['filter_phone'])) ? $this->request->get['filter_phone'] : null;
        $filter_order_status_id = (isset($this->request->get['filter_order_status_id'])) ? (int)$this->request->get['filter_order_status_id'] : null;

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : '';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : '';
        $page = (isset($this->request->get['page'])) ? $this->request->get['page'] : 1;

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_email' => $filter_email,
            'filter_phone' => $filter_phone,
            'filter_order_status_id' => (int)$filter_order_status_id,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $event_total = $this->model_extension_module_d_quick_order->getTotalOrders($filter_data);
        $orders = $this->model_extension_module_d_quick_order->getOrders($filter_data);

        $data['orders'] = array();
        $this->load->model('localisation/currency');

        foreach ($orders as $key => $order) {
            $enable = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/enable', 'id=' . $order['quick_order_id'] . $url);
            $disable = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/delete', 'id=' . $order['quick_order_id'] . $url);

            $products = $this->model_extension_module_d_quick_order->getProductsByOrderId($order['quick_order_id']);

            foreach ($products as &$product) {
                $product['price'] = number_format($product['price'], 2);
                $product['total'] = number_format($product['total'], 2);
                $product['tax'] = number_format($product['tax'], 2);
                $product['link'] = $this->model_extension_d_opencart_patch_url->link('catalog/product/edit', 'product_id=' . $product['product_id'], 'SSL');
                $product['options'] = $this->model_extension_module_d_quick_order->getOptionsByProductIdAndOrderId($product['product_id'], $order['quick_order_id']);
            }

            if (!empty($order['order_id'])) {
                $view = $this->model_extension_d_opencart_patch_url->link('sale/order/info&order_id=' . $order['order_id']);
            } else {
                $view = null;
            }

            $currency = $this->model_localisation_currency->getCurrency($order['currency_id']);

            $data['orders'][] = array(
                'id' => $order['quick_order_id'],
                'firstname' => $order['firstname'],
                'email' => $order['email'],
                'telephone' => $order['telephone'],
                'comment' => $order['comment'],
                'order_status_id' => (int)$order['order_status_id'],
                'status' => (isset($order['status'])) ? $order['status'] : 1,
                'date_added' => $order['date_added'] ? date("Y-m-d H:i", strtotime($order['date_added'])) : '',
                'create_ajax' => $enable,
                'delete_ajax' => $disable,
                'create' => $this->model_extension_d_opencart_patch_url->link($this->route . '/createOrder', 'id=' . $order['quick_order_id'] . $url),
                'delete' => $this->model_extension_d_opencart_patch_url->link($this->route . '/delete', 'id=' . $order['quick_order_id'] . $url),
                'view' => $view,
                'products' => $products,
                'currency' => $currency['symbol_left'],
            );
        }

        //sort
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : '';

        if ($sort) {
            if ($order == 'ASC') {
                $url_params['order'] = 'DESC';
            } else {
                $url_params['order'] = 'ASC';
            }
        }

        unset($url_params['sort']);
        $url = ((!empty($url_params)) ? '&' : '') . http_build_query($url_params);
        $data['sort_quick_order_id'] = $this->model_extension_d_opencart_patch_url->link($this->route, 'sort=quick_order_id' . $url);
        $data['sort_firstname'] = $this->model_extension_d_opencart_patch_url->link($this->route, 'sort=firstname' . $url);
        $data['sort_email'] = $this->model_extension_d_opencart_patch_url->link($this->route, 'sort=email' . $url);
        $data['sort_telephone'] = $this->model_extension_d_opencart_patch_url->link($this->route, 'sort=telephone' . $url);
        $data['sort_comment'] = $this->model_extension_d_opencart_patch_url->link($this->route, 'sort=comment' . $url);
        $data['sort_status'] = $this->model_extension_d_opencart_patch_url->link($this->route, 'sort=sort_status' . $url);
        $data['sort_date_added'] = $this->model_extension_d_opencart_patch_url->link($this->route, 'sort=date_added' . $url);

        //pagination
        if (isset($this->request->get['sort'])) {
            $url_params['sort'] = $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url_params['order'] = $this->request->get['order'];
        }
        unset($url_params['page']);
        $url = ((!empty($url_params)) ? '&' : '') . http_build_query($url_params);

        $pagination = new Pagination();
        $pagination->total = $event_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->model_extension_d_opencart_patch_url->link($this->route, $url . '&page={page}');
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($event_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0,
            ((($page - 1) * $this->config->get('config_limit_admin')) > ($event_total - $this->config->get('config_limit_admin'))) ? $event_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')),
            $event_total,
            ceil($event_total / $this->config->get('config_limit_admin'))
        );

        $data['filter_name'] = $filter_name;
        $data['filter_email'] = $filter_email;
        $data['filter_phone'] = $filter_phone;
        $data['filter_order_status_id'] = (int)$filter_order_status_id;

        $this->load->model('setting/store');

        $data['sort'] = $sort;
        $data['order'] = $order;

        //get setting
        $setting = $this->model_setting_setting->getSetting($this->codename, $this->store_id);
        if (!$setting) {
            $data['setting'] = $this->config->get($this->codename . '_setting');
        } else {
            $data['setting'] = $setting;
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        foreach ($data['languages'] as $key => $language) {
            if (VERSION >= '2.2.0.0') {
                $data['languages'][$key]['flag'] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
            } else {
                $data['languages'][$key]['flag'] = 'view/image/flags/' . $language['image'];
            }
        }

        $settings = $this->model_setting_setting->getSetting($this->codename, $this->store_id);
        $data['settings'] = $settings[$this->codename . '_setting'];
        $data['status'] = $settings[$this->codename . '_status'];
        $data['statuses'] = $this->config->get('d_quick_order_statuses');


        $data['tab_orders'] = $this->tabOrders($data);
        $data['tab_settings'] = $this->tabSetting($data);
        $data['tab_instructions'] = $this->tabInstructions($data);

        $this->addNecessaryStylesAndScripts();
        $this->load->model('localisation/language');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->model_extension_d_opencart_patch_load->view($this->route, $data));
    }

    public function filter()
    {
        $json = array();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('extension/d_opencart_patch/url');

            $this->filters = $this->request->post;

            $url = "";
            $lastElement = end($this->request->post);
            foreach ($this->request->post as $key => $filter) {
                if ($key == 'filter_name' && !empty($filter) && !is_null($filter) && $filter != "null") {
                    if ($filter == $lastElement) {
                        $url .= $key . "=" . $filter;
                    } else {
                        $url .= $key . "=" . $filter . "&";
                    }
                }

                if ($key == 'filter_email' && !empty($filter) && !is_null($filter) && $filter != "null") {
                    if ($filter == $lastElement) {
                        $url .= $key . "=" . $filter;
                    } else {
                        $url .= $key . "=" . $filter . "&";
                    }
                }

                if ($key == 'filter_phone' && !empty($filter) && !is_null($filter) && $filter != "null") {
                    if ($filter == $lastElement) {
                        $url .= $key . "=" . $filter;
                    } else {
                        $url .= $key . "=" . $filter . "&";
                    }
                }

                if ($key == 'filter_order_status_id') {
                    if ($filter == "*") {
                        if ($filter == $lastElement) {
                            $url = substr($url, 0, -1);
                        } else {
                            $url .= $key . "=" . $filter . "&";
                        }
                    }
                    if ($filter == "0") {
                        if ($filter == $lastElement) {
                            $url .= $key . "=" . $filter;
                        } else {
                            $url .= $key . "=" . $filter . "&";
                        }
                    }
                    if ($filter == "1") {
                        if ($filter == $lastElement) {
                            $url .= $key . "=" . $filter;
                        } else {
                            $url .= $key . "=" . $filter . "&";
                        }
                    }
                    if ($filter == "2") {
                        if ($filter == $lastElement) {
                            $url .= $key . "=" . $filter;
                        } else {
                            $url .= $key . "=" . $filter . "&";
                        }
                    }
                }
            }

            $json['redirect'] = $this->model_extension_d_opencart_patch_url->link($this->route, $url);
        } else {
            $json['error'] = $this->language->get('ajax_error_delete_empty_orderId');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function tabOrders($data)
    {
        return $this->load->view('extension/' . $this->codename . '/tab_orders', $data);
    }

    public function tabSetting($data)
    {
        return $this->load->view('extension/' . $this->codename . '/tab_setting', $data);
    }

    public function tabInstructions($data)
    {
        return $this->load->view('extension/' . $this->codename . '/tab_instruction', $data);
    }

    public function createOrder()
    {
        $json = array();

        if ($this->request->post['id']) {
            $orderId = (int)$this->request->post['id'];
            $this->load->model($this->route);

            $currentOrder = $this->model_extension_module_d_quick_order->getOrderById($orderId);
            if ($currentOrder && $currentOrder['order_status_id'] == 0) {

//              Create new Order
                unset($currentOrder['order_id']);
                $currentOrder['custom_field'] = array();
                $currentOrder['payment_custom_field'] = array();
                $currentOrder['shipping_custom_field'] = array();
                $currentOrder['order_status_id'] = 2;

                $lastid = $this->model_extension_module_d_quick_order->replaceOrder($currentOrder);

//              Create new ProductOrders
                $products = $this->model_extension_module_d_quick_order->getProductsById($orderId);
                foreach ($products as $product) {
                    $dataToNewProductOrder = $this->prepareReplaceProductOrder($lastid, $product);
                    $this->model_extension_module_d_quick_order->replaceProductsOrder($dataToNewProductOrder);

                    $productOptions = $this->model_extension_module_d_quick_order->getOptionsByProductIdAndOrderId($product['product_id'], $orderId);
                    foreach ($productOptions as &$productOption) {
                        $data['order_id'] = $lastid;
                        $data['order_product_id'] = $productOption['product_to_order_id'];
                        $data['product_option_id'] = $productOption['product_option_id'];
                        $data['product_option_value_id'] = $productOption['product_option_value_id'];
                        $data['name'] = $productOption['name'];
                        $data['value'] = $productOption['value'];
                        $data['type'] = $productOption['type'];

                        $this->model_extension_module_d_quick_order->replaceProductOptionsOrder($data);
                    }
                }

//              Change status order and set real order_id
//                $this->load->config('d_quick_order');
//                $statuses = $this->config->get('d_quick_order_statuses');

                $this->load->model('extension/d_opencart_patch/url');
                $this->model_extension_module_d_quick_order->updateOrderStatusAndSetRealOrder($orderId, 1, $lastid);

//              Redirect
                $json['redirect'] = $this->model_extension_d_opencart_patch_url->link('sale/order/edit/' . "order_id=$lastid");

            } else {
                $json['error'] = $this->language->get('ajax_error_delete_order_status');
            }
        } else {
            $json['error'] = $this->language->get('ajax_error_delete_empty_orderId');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public
    function addNecessaryStylesAndScripts()
    {
        $this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
        $this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.min.css');
        $this->document->addScript('view/javascript/d_bootstrap_tagsinput/bootstrap-tagsinput.js');
        $this->document->addStyle('view/javascript/d_bootstrap_tagsinput/bootstrap-tagsinput.css');

        $this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
        $this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');

        $this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
        $this->document->addStyle('view/stylesheet/d_quick_order.css');

        $this->document->addStyle('view/javascript/d_bootstrap_colorpicker/css/bootstrap-colorpicker.css');
        $this->document->addScript('view/javascript/d_bootstrap_colorpicker/js/bootstrap-colorpicker.js');
    }

    public
    function setBreadcrumbsData($data)
    {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->model_extension_d_opencart_patch_url->link('common/home'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->model_extension_d_opencart_patch_url->getExtensionLink('module'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->model_extension_d_opencart_patch_url->link($this->route),
            'separator' => ' :: '
        );

        return $data;
    }

    public
    function prepareDataToPage($url)
    {
        // Heading
        $this->document->setTitle($this->language->get('heading_title_main'));
        $data['heading_title'] = $this->language->get('heading_title_main');
        $data['text_edit'] = $this->language->get('text_edit');

        //      Breadcrumbs
        $data = $this->setBreadcrumbsData($data);

        // Variable
        $data['codename'] = $this->codename;
        $data['codename_setting'] = $this->codename . "_setting";
        $data['route'] = $this->route;
        $data['version'] = $this->extension['version'];
        $data['token'] = $this->model_extension_d_opencart_patch_user->getToken();
        $data['d_shopunity'] = $this->d_shopunity;

        // Customer
        $data['tab_event'] = $this->language->get('tab_event');

        $data['tab_heading_orders'] = $this->language->get('tab_heading_orders');
        $data['tab_heading_setting'] = $this->language->get('tab_heading_setting');
        $data['tab_heading_instruction'] = $this->language->get('tab_heading_instruction');

        // Tab orders: filters
        $data['column_product_name'] = $this->language->get('column_product_name');
        $data['column_customer_email'] = $this->language->get('column_customer_email');
        $data['column_customer_phone'] = $this->language->get('column_customer_phone');
        $data['column_product_status'] = $this->language->get('column_product_status');

        // Tab orders: sort columns
        $data['column_order_id_number'] = $this->language->get('column_order_id_number');
        $data['column_customer_name'] = $this->language->get('column_customer_name');
        $data['column_customer_email'] = $this->language->get('column_customer_email');
        $data['column_customer_phone'] = $this->language->get('column_customer_phone');
        $data['column_customer_comment'] = $this->language->get('column_customer_comment');
        $data['column_products_images'] = $this->language->get('column_products_images');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_sort_status'] = $this->language->get('column_sort_status');
        $data['column_action'] = $this->language->get('column_action');


        // Tab settings: tabs
        $data['tab_basic_setting'] = $this->language->get('tab_basic_setting');
        $data['tab_button_setting'] = $this->language->get('tab_button_setting');
        $data['tab_modal_window_setting'] = $this->language->get('tab_modal_window_setting');
        $data['tab_modal_fields_setting'] = $this->language->get('tab_modal_fields_setting');

        $data['tab_basic_setting_enable'] = $this->language->get('tab_basic_setting_enable');
        $data['help_module_selector'] = $this->language->get('help_module_selector');
        $data['tab_basic_setting_selector'] = $this->language->get('tab_basic_setting_selector');
        $data['tab_modal_button_name'] = $this->language->get('tab_modal_button_name');
        $data['tab_modal_button_style_color'] = $this->language->get('tab_modal_button_style_color');
        $data['tab_modal_button_style_border'] = $this->language->get('tab_modal_button_style_border');
        $data['tab_modal_button_style_bgColor'] = $this->language->get('tab_modal_button_style_bgColor');
        $data['tab_modal_button_style_hover_color'] = $this->language->get('tab_modal_button_style_hover_color');
        $data['tab_modal_button_style_hover_border'] = $this->language->get('tab_modal_button_style_hover_border');
        $data['tab_modal_button_style_hover_bgColor'] = $this->language->get('tab_modal_button_style_hover_bgColor');

        $data['tab_modal_modal_setting_title'] = $this->language->get('tab_modal_modal_setting_title');
        $data['tab_modal_modal_setting_description'] = $this->language->get('tab_modal_modal_setting_description');
        $data['tab_modal_modal_setting_button_submit'] = $this->language->get('tab_modal_modal_setting_button_submit');

        $data['tab_modal_fields_setting_name'] = $this->language->get('tab_modal_fields_setting_name');
        $data['tab_modal_fields_setting_name_require'] = $this->language->get('tab_modal_fields_setting_name_require');
        $data['tab_modal_fields_setting_email'] = $this->language->get('tab_modal_fields_setting_email');
        $data['tab_modal_fields_setting_email_require'] = $this->language->get('tab_modal_fields_setting_email_require');
        $data['tab_modal_fields_setting_comment'] = $this->language->get('tab_modal_fields_setting_comment');
        $data['tab_modal_fields_setting_comment_require'] = $this->language->get('tab_modal_fields_setting_comment_require');
        $data['tab_modal_fields_setting_phone_format'] = $this->language->get('tab_modal_fields_setting_phone_format');


        $data['text_list'] = $this->language->get('text_list');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_default'] = $this->language->get('text_default');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');

        $data['column_code'] = $this->language->get('column_code');
        $data['column_trigger'] = $this->language->get('column_trigger');
        $data['column_action'] = $this->language->get('column_action');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_action'] = $this->language->get('column_action');
        $data['column_sort_order'] = $this->language->get('column_sort_order');

        $data['entry_code'] = $this->language->get('entry_code');
        $data['entry_trigger'] = $this->language->get('entry_trigger');
        $data['entry_action'] = $this->language->get('entry_action');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_date_added'] = $this->language->get('entry_date_added');

        $data['button_enable'] = $this->language->get('button_enable');
        $data['button_disable'] = $this->language->get('button_disable');
        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_filter'] = $this->language->get('button_filter');

        // Tab
        $data['tab_setting'] = $this->language->get('tab_setting');

        // Button Events
        $data['button_save'] = $this->language->get('button_save');
        $data['button_save_action'] = $this->model_extension_d_opencart_patch_url->link($this->route . '/save', $url);
        $data['button_save_and_stay_action'] = $this->model_extension_d_opencart_patch_url->link($this->route . '/save', $url);
        $data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
        $data['button_filter_action'] = $this->model_extension_d_opencart_patch_url->link($this->route . '/filter', $url);
        $data['button_create'] = $this->language->get('button_create');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_remove'] = $this->language->get('button_remove');
        $data['button_uninstall'] = $this->model_extension_d_opencart_patch_url->link($this->route . '/uninstallModule', $url);
        $data['button_uninstall_text'] = $this->language->get('button_uninstall');


        // Entry
        $data['entry_compatibility'] = $this->language->get('entry_compatibility');
        $data['entry_skipped_models'] = $this->language->get('entry_skipped_models');
        $data['text_order_create'] = $this->language->get('text_order_create');
        $data['text_order_delete'] = $this->language->get('text_order_delete');
        $data['text_order_view'] = $this->language->get('text_order_view');
        $data['help_skipped_models'] = $this->language->get('help_skipped_models');
        $data['entry_test_toggle'] = $this->language->get('entry_test_toggle');
        $data['entry_test'] = $this->language->get('entry_test');
        $data['text_install'] = $this->language->get('text_install');
        $data['text_uninstall'] = $this->language->get('text_uninstall');

        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_config_files'] = $this->language->get('entry_config_files');
        $data['entry_select'] = $this->language->get('entry_select');
        $data['entry_text'] = $this->language->get('entry_text');
        $data['entry_radio'] = $this->language->get('entry_radio');
        $data['entry_checkbox'] = $this->language->get('entry_checkbox');
        $data['entry_color'] = $this->language->get('entry_color');
        $data['entry_image'] = $this->language->get('entry_image');
        $data['entry_textarea'] = $this->language->get('entry_textarea');

        // Text
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        //field
        $data['entry_field'] = $this->language->get('entry_field');
        $data['entry_type'] = $this->language->get('entry_type');

        //action
        $data['module_link'] = $this->model_extension_d_opencart_patch_url->ajax($this->route);
        $data['action'] = $this->model_extension_d_opencart_patch_url->link($this->route, $url);
        $data['filter'] = $this->model_extension_d_opencart_patch_url->link($this->route, $url);
        $data['create'] = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/create', $url);
        $data['delete'] = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/delete', $url);
        $data['install_test'] = $this->model_extension_d_opencart_patch_url->link($this->route . '/install_test', $url);
        $data['uninstall_test'] = $this->model_extension_d_opencart_patch_url->link($this->route . '/uninstall_test', $url);
        $data['install_compatibility'] = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/install_compatibility', $url);
        $data['uninstall_compatibility'] = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/uninstall_compatibility', $url);
        $data['autocomplete'] = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/autocomplete');
        $data['cancel'] = $this->model_extension_d_opencart_patch_url->getExtensionLink('module');

        //instruction
        $data['tab_instruction'] = $this->language->get('tab_instruction');
        $data['text_instruction'] = $this->language->get('text_instruction');

        //get store
        $data['store_id'] = $this->store_id;
        $data['stores'] = $this->model_extension_d_opencart_patch_store->getAllStores();

        $data['conflict_models'] = false;
        $data['compatibility'] = $this->model_extension_d_opencart_patch_modification->getModificationByName('d_quick_order');
        $data['compatibility_required'] = false;
        if (VERSION < '3.0.0.0') {
            $data['compatibility_required'] = true;
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if (isset($this->session->data['error'])) {
            $data['error']['warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        return $data;
    }

    public
    function validateAjaxOrderAndReturnFiltersData($data)
    {
        $filtered = array();

        return $filtered;
    }

    public
    function setup()
    {
        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('setting/setting');
        $this->load->config('d_quick_order');

//      Generate settings
        $setting = array();
        $setting[$this->codename . '_setting'] = $this->config->get($this->codename . '_setting');
        $setting[$this->codename . '_status'] = 1;

        $new_setting = array();
        foreach ($setting as $k => $v) {
            $new_setting['module_' . $k] = $v;
        }

//        Set new settings
        $this->model_setting_setting->editSetting('module_' . $this->codename, $new_setting, $this->store_id);
        $this->model_setting_setting->editSetting($this->codename, $setting, $this->store_id);

        $this->createTables();
        $this->uninstallEvents();
        $this->installEventsAndEnable();

        $this->session->data['success'] = $this->language->get('success_setup');
        $json['redirect'] = $this->model_extension_d_opencart_patch_url->ajax($this->route);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public
    function setupView()
    {
        $this->load->model('extension/d_opencart_patch/load');
        $this->load->model('extension/d_opencart_patch/url');

        $this->load->language($this->route);

        if ($this->d_admin_style) {
            $this->load->model('extension/d_admin_style/style');

            $this->model_extension_d_admin_style_style->getAdminStyle('light');
        }

        $url_params = array();

        if (isset($this->response->get['store_id'])) {
            $url_params['store_id'] = $this->store_id;
        }

        $url = ((!empty($url_params)) ? '&' : '') . http_build_query($url_params);

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->model_extension_d_opencart_patch_url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->model_extension_d_opencart_patch_url->link('marketplace/extension', 'type=module')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->model_extension_d_opencart_patch_url->link('marketplace/extension', $url)
        );

        // Notification
        foreach ($this->error as $key => $error) {
            $data['error'][$key] = $error;
        }

        // Heading
        $this->document->setTitle($this->language->get('heading_title_main'));
        $data['heading_title'] = $this->language->get('heading_title_main');
        $data['text_edit'] = $this->language->get('text_edit');

        $data['version'] = $this->extension['version'];

        $data['text_welcome_title'] = $this->language->get('text_welcome_title');
        $data['text_welcome_description'] = $this->language->get('text_welcome_description');

        $data['text_welcome_visual_editor'] = $this->language->get('text_welcome_visual_editor');
        $data['text_welcome_building_blocks'] = $this->language->get('text_welcome_building_blocks');
        $data['text_welcome_mobile_ready'] = $this->language->get('text_welcome_mobile_ready');
        $data['text_welcome_increase_sales'] = $this->language->get('text_welcome_increase_sales');

        $data['button_setup'] = $this->language->get('button_setup');
        $data['checkbox_setup'] = $this->language->get('checkbox_setup');
        $data['quick_setup'] = $this->model_extension_d_opencart_patch_url->ajax($this->route . '/setup');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->model_extension_d_opencart_patch_load->view('extension/' . $this->codename . '/welcome', $data));
    }

    public
    function install()
    {
        if (!$this->validate()) {
            return false;
        }

        if ($this->d_event_manager) {
            $this->load->model('extension/module/d_event_manager');
            $this->createTables();
        }

        if ($this->d_shopunity) {
            $this->load->model('extension/d_shopunity/mbooth');
            $this->model_extension_d_shopunity_mbooth->installDependencies($this->codename . '_admin');
        }

        if ($this->d_twig_manager) {
            $this->load->model('extension/module/d_twig_manager');
            $this->model_extension_module_d_twig_manager->installCompatibility();
        }

        $this->createTables();
    }

    public
    function installEvents()
    {
        if ($this->d_event_manager) {
            $this->load->model('extension/module/d_event_manager');

            $this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/controller/common/footer/before', 'extension/module/d_quick_order/catalog_controller_common_footer_before', 0);
            $this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/controller/common/header/before', 'extension/module/d_quick_order/catalog_controller_common_header_before', 0);
            $this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/product/after', 'extension/module/d_quick_order/catalog_view_product_product_after', 0);
        }
    }

    public
    function installEventsAndEnable()
    {
        if ($this->d_event_manager) {
            $this->load->model('extension/module/d_event_manager');

            $this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/controller/common/footer/before', 'extension/module/d_quick_order/catalog_controller_common_footer_before', 1);
            $this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/controller/common/header/before', 'extension/module/d_quick_order/catalog_controller_common_header_before', 1);
            $this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/product/after', 'extension/module/d_quick_order/catalog_view_product_product_after', 1);
        }
    }

    public
    function uninstallEvents()
    {
        if ($this->d_event_manager) {
            $this->load->model('extension/module/d_event_manager');

            $this->model_extension_module_d_event_manager->deleteEvent($this->codename);
        }
    }

    public
    function uninstall()
    {
        $this->load->model($this->route);
        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('extension/d_opencart_patch/modification');
        $this->load->model('setting/setting');

        $this->deleteTables();
        $this->uninstallEvents();

        $this->model_setting_setting->deleteSetting($this->codename, $this->store_id);
        $this->model_setting_setting->deleteSetting('module_' . $this->codename, $this->store_id);

        $this->model_extension_d_opencart_patch_modification->setModification('d_quick_order.xml', 0);
        $this->model_extension_d_opencart_patch_modification->refreshCache();

        $json['success'] = $this->language->get('ajax_success_uninstall');
        $this->session->data['success'] = $this->language->get('ajax_success_uninstall');

        $json['redirect'] = $this->model_extension_d_opencart_patch_url->link('marketplace/extension');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


    public
    function uninstallModule()
    {
        $this->load->model($this->route);
        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('extension/d_opencart_patch/modification');
        $this->load->model('setting/setting');

        $this->deleteTables();
        $this->uninstallEvents();

        $this->model_setting_setting->deleteSetting($this->codename, $this->store_id);
        $this->model_setting_setting->deleteSetting('module_' . $this->codename, $this->store_id);

        $this->model_extension_d_opencart_patch_modification->setModification('d_quick_order.xml', 0);
        $this->model_extension_d_opencart_patch_modification->refreshCache();

        $json['success'] = $this->language->get('ajax_success_uninstall');
        $this->session->data['success'] = $this->language->get('ajax_success_uninstall');

        $json['redirect'] = $this->model_extension_d_opencart_patch_url->link('marketplace/extension');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public
    function isSetup()
    {
        $this->load->model('extension/d_opencart_patch/extension');
        if (!$this->model_extension_d_opencart_patch_extension->isInstalled($this->codename)) {
            return false;
        }
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting($this->codename);
        if (!$settings) {
            return false;
        }
        return true;
    }

    public
    function createTables()
    {
        $this->load->model($this->route);

        $this->model_extension_module_d_quick_order->createOrdersTable();
        $this->model_extension_module_d_quick_order->createOrdersProductTable();
        $this->model_extension_module_d_quick_order->createOrdersOptionTable();
    }

    public
    function deleteTables()
    {
        $this->load->model($this->route);

        $this->model_extension_module_d_quick_order->deleteOrdersTable();
        $this->model_extension_module_d_quick_order->deleteOrdersProductTable();
        $this->model_extension_module_d_quick_order->deleteOrdersOptionTable();
    }

    public
    function addSettings()
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/d_link_cart');
        $data = array();
        $new_data = array();
        $data['d_link_cart_status'] = 1;
        $new_data['module_d_link_cart_status'] = 1;
        $this->model_extension_module_d_link_cart->addToLayoutFromSetup();

        $this->model_setting_setting->editSetting('module_' . $this->codename, $new_data);
        $this->model_setting_setting->editSetting($this->codename, $data);
        $this->response->setOutput($this->model_extension_d_opencart_patch_load->view('extension/module/d_link_cart', $data));
    }

    public function save()
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/d_opencart_patch/url');

        $this->model_setting_setting->editSetting($this->codename, $this->request->post, $this->store_id);

        $new_setting = array();
        foreach ($this->request->post as $k => $v) {
            $new_setting['module_' . $k] = $v;
        }

        $json['success'] = $this->language->get('success_modifed');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected
    function validate()
    {
        if (!$this->user->hasPermission('modify', $this->route)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public
    function delete()
    {
        $json = array();

        if ($this->request->post['id']) {
            $orderId = (int)$this->request->post['id'];

            $this->load->model($this->route);
            $this->load->model('extension/d_opencart_patch/url');

            $this->model_extension_module_d_quick_order->deleteOrder($orderId);
            $this->model_extension_module_d_quick_order->deleteProductsOrder($orderId);
            $this->model_extension_module_d_quick_order->deleteProductOptions($orderId);

            $json['redirect'] = $this->model_extension_d_opencart_patch_url->link($this->route);
        } else {
            $json['error'] = $this->language->get('ajax_error_delete');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public
    function prepareReplaceOrder($order_data)
    {
        $data['store_id'] = $order_data['store_id'];
        $data['store_url'] = $order_data['store_url'];
        $data['store_name'] = $order_data['store_name'];
        $data['invoice_no'] = $order_data['invoice_no'];
        $data['invoice_prefix'] = $order_data['invoice_prefix'];
        $data['customer_id'] = $order_data['customer_id'];
        $data['customer_group_id'] = $order_data['customer_group_id'];
        $data['firstname'] = $order_data['firstname'];
        $data['lastname'] = $order_data['lastname'];
        $data['email'] = $order_data['email'];
        $data['telephone'] = $order_data['telephone'];
        $data['fax'] = $order_data['fax'];
        $data['custom_field'] = $order_data['custom_field'];

        $data['payment_firstname'] = $order_data['payment_firstname'];
        $data['payment_lastname'] = $order_data['payment_lastname'];
        $data['payment_company'] = $order_data['payment_company'];
        $data['payment_address_1'] = $order_data['payment_address_1'];
        $data['payment_address_2'] = $order_data['payment_address_2'];
        $data['payment_city'] = $order_data['payment_city'];
        $data['payment_postcode'] = $order_data['payment_postcode'];
        $data['payment_zone'] = $order_data['payment_zone'];
        $data['payment_zone_id'] = $order_data['payment_zone_id'];
        $data['payment_country'] = $order_data['payment_country'];
        $data['payment_country_id'] = $order_data['payment_country_id'];
        $data['payment_address_format'] = $order_data['payment_address_format'];
        $data['payment_custom_field'] = $order_data['payment_custom_field'];
        $data['payment_method'] = $order_data['payment_method'];
        $data['payment_code'] = $order_data['payment_code'];

        $data['shipping_firstname'] = $order_data['shipping_firstname'];
        $data['shipping_lastname'] = $order_data['shipping_lastname'];
        $data['shipping_company'] = $order_data['shipping_company'];
        $data['shipping_address_1'] = $order_data['shipping_address_1'];
        $data['shipping_address_2'] = $order_data['shipping_address_2'];
        $data['shipping_postcode'] = $order_data['shipping_postcode'];
        $data['shipping_city'] = $order_data['shipping_city'];
        $data['shipping_country'] = $order_data['shipping_country'];
        $data['shipping_country_id'] = $order_data['shipping_country_id'];
        $data['shipping_zone'] = $order_data['shipping_zone'];
        $data['shipping_zone_id'] = $order_data['shipping_zone_id'];
        $data['shipping_address_format'] = $order_data['shipping_address_format'];
        $data['shipping_custom_field'] = $order_data['shipping_custom_field'];
        $data['shipping_method'] = $order_data['shipping_method'];
        $data['shipping_code'] = $order_data['shipping_code'];

        $data['comment'] = $order_data['comment'];
        $data['total'] = $order_data['total'];

        $data['order_status_id'] = $order_data['order_status_id'];
        $data['affiliate_id'] = $order_data['affiliate_id'];
        $data['commission'] = $order_data['commission'];
        $data['marketing_id'] = $order_data['marketing_id'];
        $data['tracking'] = $order_data['tracking'];
        $data['language_id'] = $order_data['language_id'];

        $data['affiliate_id'] = $order_data['affiliate_id'];
        $data['commission'] = $order_data['commission'];
        $data['marketing_id'] = $order_data['marketing_id'];
        $data['tracking'] = $order_data['tracking'];
        $data['language_id'] = $order_data['language_id'];
        $data['currency_id'] = $order_data['currency_id'];
        $data['currency_code'] = $order_data['currency_code'];
        $data['currency_value'] = $order_data['currency_value'];
        $data['ip'] = $order_data['ip'];
        $data['forwarded_ip'] = $order_data['forwarded_ip'];
        $data['user_agent'] = $order_data['user_agent'];
        $data['accept_language'] = $order_data['accept_language'];

        return $data;
    }

    public
    function prepareReplaceProductOrder($order_id, $order_product_data)
    {
        $data['order_id'] = $order_id;
        $data['product_id'] = $order_product_data['product_id'];
        $data['name'] = $order_product_data['name'];
        $data['model'] = $order_product_data['model'];
        $data['quantity'] = $order_product_data['quantity'];
        $data['price'] = $order_product_data['price'];
        $data['total'] = $order_product_data['total'];
        $data['tax'] = $order_product_data['tax'];
        $data['reward'] = $order_product_data['reward'];

        return $data;
    }

    public
    function dd($data)
    {
        highlight_string("<?php\n\$data =\n" . var_export($data, true) . ";\n?>");
        die();
    }
}