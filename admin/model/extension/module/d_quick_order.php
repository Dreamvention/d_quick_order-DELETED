<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23.05.2019
 * Time: 9:56
 */

class ModelExtensionModuleDQuickOrder extends Model
{
    public $table = "d_qo_product_to_order";

//    todo: addOrder
    public function store($data)
    {
        return $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "d_qo_order SET 
            product_id = '" . (int)$data['product_id'] . "', 
            product_name = '" . $this->db->escape($data['product_name']) . "', 
            product_description = '" . $this->db->escape($data['product_description']) . "', 
            product_price = '" . $this->db->escape($data['product_price']) . "', 
            customer_phone = '" . $this->db->escape($data['customer_phone']) . "', 
            customer_comment = '" . $this->db->escape($data['customer_comment']) . "', 
            status = 'open',
            date_added = NOW(), 
            date_modified = NOW();");
    }

    public function getOrderById($id)
    {
        $query = $this->db->query("SELECT *  FROM " . DB_PREFIX . "d_qo_order WHERE quick_order_id = '". $id ."' LIMIT 1");

        return $query->row;
    }

    public function getOrders($data = array())
    {
        $sql = "SELECT ";

        if (!empty($data['unique'])) {
            $sql .= " DISTINCT ";
        }

        $sql .= " * FROM `" . DB_PREFIX . "d_qo_order` ";

        $implode = array();

        if (!empty($data['filter_name'])) {
            $implode[] = "`firstname` LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $implode[] = "`email` LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (!empty($data['filter_phone'])) {
            $implode[] = "`telephone` LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }

        if (isset($data['filter_status_id']) && !is_null($data['filter_status_id'])) {
            $implode[] = "`order_status_id` = '" . (int)$data['filter_status_id'] . "'";
        }


        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        if (!empty($data['unique'])) {
            $sql .= " GROUP BY customer_session_id ";
        }

        $sort_data = array(
            'quick_order_id',
            'firstname',
            'email',
            'telephone',
            'comment',
            'status',
            'date_added',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY `" . $data['sort'] . "`";
        } else {
            $sql .= " ORDER BY `date_added`";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductsByOrderId($quick_order_id)
    {
        $query = $this->db->query("SELECT op.*,p.image , op.*,p.model FROM " . DB_PREFIX . "d_qo_product_to_order op LEFT JOIN " . DB_PREFIX . "product p ON (p.product_id = op.product_id)  WHERE quick_order_id = '" . (int)$quick_order_id . "'");

        return $query->rows;
    }

    public function getProductsById($id)
    {
        $query = $this->db->query("SELECT *  FROM " . DB_PREFIX . "d_qo_product_to_order WHERE quick_order_id = '". $id ."'");

        return $query->rows;
    }

    public function getTotalOrders($data = array())
    {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "d_qo_order";

        $implode = array();

        if (!empty($data['filter_name'])) {
            $implode[] = "`firstname` LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $implode[] = "`email` LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (!empty($data['filter_phone'])) {
            $implode[] = "`telephone` LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }

        if (isset($data['filter_status_id']) && !is_null($data['filter_status_id'])) {
            $implode[] = "`order_status_id` = '" . (int)$data['filter_status_id'] . "'";
        }

        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function createOrdersTable()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "d_qo_order` (
              `quick_order_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
              `invoice_no` int(11) NOT NULL DEFAULT '0',
              `invoice_prefix` varchar(26) NOT NULL,
              `store_id` int(11) NOT NULL DEFAULT '0',
              `store_name` varchar(64) NOT NULL,
              `store_url` varchar(255) NOT NULL,
              `customer_id` int(11) NOT NULL DEFAULT '0',
              `customer_group_id` int(11) NOT NULL DEFAULT '0',
              `firstname` varchar(32) NOT NULL,
              `lastname` varchar(32) NOT NULL,
              `email` varchar(96) NOT NULL,
              `telephone` varchar(32) NOT NULL,
              `fax` varchar(32) NOT NULL,
              `custom_field` text NOT NULL,
              `payment_firstname` varchar(32) NOT NULL,
              `payment_lastname` varchar(32) NOT NULL,
              `payment_company` varchar(60) NOT NULL,
              `payment_address_1` varchar(128) NOT NULL,
              `payment_address_2` varchar(128) NOT NULL,
              `payment_city` varchar(128) NOT NULL,
              `payment_postcode` varchar(10) NOT NULL,
              `payment_country` varchar(128) NOT NULL,
              `payment_country_id` int(11) NOT NULL,
              `payment_zone` varchar(128) NOT NULL,
              `payment_zone_id` int(11) NOT NULL,
              `payment_address_format` text NOT NULL,
              `payment_custom_field` text NOT NULL,
              `payment_method` varchar(128) NOT NULL,
              `payment_code` varchar(128) NOT NULL,
              `shipping_firstname` varchar(32) NOT NULL,
              `shipping_lastname` varchar(32) NOT NULL,
              `shipping_company` varchar(40) NOT NULL,
              `shipping_address_1` varchar(128) NOT NULL,
              `shipping_address_2` varchar(128) NOT NULL,
              `shipping_city` varchar(128) NOT NULL,
              `shipping_postcode` varchar(10) NOT NULL,
              `shipping_country` varchar(128) NOT NULL,
              `shipping_country_id` int(11) NOT NULL,
              `shipping_zone` varchar(128) NOT NULL,
              `shipping_zone_id` int(11) NOT NULL,
              `shipping_address_format` text NOT NULL,
              `shipping_custom_field` text NOT NULL,
              `shipping_method` varchar(128) NOT NULL,
              `shipping_code` varchar(128) NOT NULL,
              `comment` text NOT NULL,
              `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
              `order_status_id` int(11) NOT NULL DEFAULT '0',
              `affiliate_id` int(11) NOT NULL,
              `commission` decimal(15,4) NOT NULL,
              `marketing_id` int(11) NOT NULL,
              `tracking` varchar(64) NOT NULL,
              `language_id` int(11) NOT NULL,
              `currency_id` int(11) NOT NULL,
              `currency_code` varchar(3) NOT NULL,
              `currency_value` decimal(15,8) NOT NULL DEFAULT '1.00000000',
              `ip` varchar(40) NOT NULL,
              `forwarded_ip` varchar(40) NOT NULL,
              `user_agent` varchar(255) NOT NULL,
              `accept_language` varchar(255) NOT NULL,
              `date_added` datetime NOT NULL,
              `date_modified` datetime NOT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }

    public function deleteOrdersTable()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "d_qo_order`");
    }

    public function createOrdersProductTable()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "d_qo_product_to_order` (
            `product_to_order_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
            `quick_order_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `name` varchar(191) NOT NULL, 
            `model` varchar(191) NULL, 
            `quantity` int(4) NOT NULL,
            `price` decimal(15,4) NOT NULL,
            `total` decimal(15,4) NOT NULL,
            `tax` decimal(15,4) NOT NULL,
            `reward` int(4) NOT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }

    public function deleteOrdersProductTable()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "d_qo_product_to_order`");
    }

    public function uninstallDatabase()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "d_qo_order`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "d_qo_product_to_order`");
    }

    public function isInstalled()
    {
        return $this->db->query("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA='" . $this->db->escape(DB_DATABASE) . "' AND TABLE_NAME='" . DB_PREFIX . "superquickcheckout_order'")->num_rows > 0;
    }

    public function getGroupId()
    {
        if (VERSION == '2.0.0.0') {
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE user_id = '" . $this->user->getId() . "'");
            $user_group_id = (int)$user_query->row['user_group_id'];
        } else {
            $user_group_id = $this->user->getGroupId();
        }

        return $user_group_id;
    }

    public function editOrder($order_id, $data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "d_qo_order SET 
            `firstname	` = '" . $this->db->escape($data['firstname']) . "',
            `email` = '" . $this->db->escape($data['email']) . "',
            `telephone` = '" . $this->db->escape($data['telephone']) . "'
            `comment` = '" . $this->db->escape($data['comment']) . "'
            WHERE quick_order_id = '" . (int)$order_id . "'");
    }

    public function getAllStatuses()
    {
        $statuses = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status");

        return $statuses->rows;
    }

    public function deleteOrder($order_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "d_qo_order` WHERE quick_order_id='" . (int)$order_id . "'");
    }

    public function deleteProductsOrder($order_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "d_qo_product_to_order` WHERE quick_order_id='" . (int)$order_id . "'");
    }

    public function replaceOrder($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape($data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', ip = '" . $this->db->escape($data['ip']) . "', forwarded_ip = '" . $this->db->escape($data['forwarded_ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', accept_language = '" . $this->db->escape($data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");


//        return $this->db->query("INSERT INTO oc_order
//                SELECT * FROM oc_d_qo_order
//                WHERE oc_d_qo_order.quick_order_id = '" . (int)$order_id . "'");
    }

    public function replaceProductsOrder($data)
    {
        return $this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$data['order_id'] . "', product_id = '" . (int)$data['product_id'] . "', name = '" . $this->db->escape($data['name']) . "', model = '" . $this->db->escape($data['model']) . "', quantity = '" . (int)$data['quantity'] . "', price = '" . (float)$data['price'] . "', total = '" . (float)$data['total'] . "', tax = '" . (float)$data['tax'] . "', reward = '" . (int)$data['reward'] . "'");

//        return $this->db->query("INSERT INTO oc_order_product (Col1, Col2, ..., ColN)
//                SELECT * FROM oc_d_qo_product_to_order
//                WHERE oc_d_qo_product_to_order.quick_order_id = '" . (int)$order_id . "'");
    }

    public function updateOrderStatus($order_id, $status)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "d_qo_order SET 
            `order_status_id` = '" . (int)$status . "'
            WHERE quick_order_id = '" . (int)$order_id . "'");
    }
}