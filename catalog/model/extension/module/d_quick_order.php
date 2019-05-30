<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23.05.2019
 * Time: 9:56
 */

class ModelExtensionModuleDQuickOrder extends Model
{
    public $tableName = "d_qo_order";

    public function addOrder($data) {

//        var_dump($data);
//        die();

//        foreach ($data as $key => $val) {
//            print_r($key);
//            print_r(" - ");
//            print_r(gettype($val));
//            print_r($val);
//
//            print_r("</hr>");
//            print_r("<hr/>");
//        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "d_qo_order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape($data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', ip = '" . $this->db->escape($data['ip']) . "', forwarded_ip = '" .  $this->db->escape($data['forwarded_ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', accept_language = '" . $this->db->escape($data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");

        $order_id = $this->db->getLastId();

        return $order_id;
    }

    public function productToOrder($product)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "d_qo_product_to_order SET order_id = '" . (int)$product['order_id'] . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

        $order_id = $this->db->getLastId();

        return $order_id;

//        return $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "d_qo_order SET
//            product_id = '" . (int)$data['product_id'] . "',
//            product_name = '" . $this->db->escape($data['product_name']) . "',
//            product_link = '" . $this->db->escape($data['product_link']) . "',
//            product_price = '" . $this->db->escape($data['product_price']) . "',
//            product_amount = '" . (int)$this->db->escape($data['product_amount']) . "',
//            customer_session_id = '" . $this->db->escape($data['customer_session_id']) . "',
//            customer_name = '" . $this->db->escape($data['customer_name']) . "',
//            customer_email = '" . $this->db->escape($data['customer_email']) . "',
//            customer_phone = '" . $this->db->escape($data['customer_phone']) . "',
//            customer_comment = '" . $this->db->escape($data['customer_comment']) . "',
//            status = 'open',
//            date_added = NOW(),
//            date_modified = NOW();");
    }

    public function getOrderById($id)
    {
        $query = $this->db->query("SELECT id  FROM " . DB_PREFIX . "$this->tableName WHERE id = '". $id ."' LIMIT 1");

        return $query->row;
    }
}