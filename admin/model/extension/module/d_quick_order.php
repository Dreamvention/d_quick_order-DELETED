<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23.05.2019
 * Time: 9:56
 */

class ModelExtensionModuleDQuickOrder extends Model
{
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

    public function getOrders($data = array())
    {
        $sql = "SELECT ";

        if (!empty($data['unique'])) {
            $sql .= " DISTINCT ";
        }

        $sql .= " * FROM `" . DB_PREFIX . "d_qo_order` ";

        $implode = array();

        if (!empty($data['filter_code'])) {
            $implode[] = "`product_name` LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
        }

        if (!empty($data['filter_trigger'])) {
            $implode[] = "`customer_phone` LIKE '%" . $this->db->escape($data['filter_trigger']) . "%'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $implode[] = "`status` = '" . (int)$data['filter_status'] . "'";
        }


        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        if (!empty($data['unique'])) {
            $sql .= " GROUP BY customer_session_id ";
        }

        $sort_data = array(
            'product_name',
            'customer_phone',
            'status',
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY `" . $data['sort'] . "`";
        } else {
            $sql .= " ORDER BY `date_added`";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
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

    public function getTotalOrders($data = array())
    {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "d_qo_order";

        $implode = array();

        if (!empty($data['filter_code'])) {
            $implode[] = "`code` LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
        }

        if (!empty($data['filter_trigger'])) {
            $implode[] = "`trigger` LIKE '%" . $this->db->escape($data['filter_trigger']) . "%'";
        }

        if (!empty($data['filter_action'])) {
            $implode[] = "`action` LIKE '%" . $this->db->escape($data['filter_action']) . "%'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $implode[] = "`status` = '" . (int)$data['filter_status'] . "'";
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
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."d_qo_order`");
    }

    public function createOrdersProductTable()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "d_qo_product_to_order` (
            `product_to_order_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
            `order_id` INT NOT NULL,
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
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."d_qo_product_to_order`");
    }

    public function uninstallDatabase(){
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."d_qo_order`");
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."d_qo_product_to_order`");
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

    public function deleteOrder($order_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "d_qo_order` WHERE quick_order_id='" . (int)$order_id . "'");
    }

    public function deleteProductsOrder($order_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "d_qo_product_to_order` WHERE order_id='" . (int)$order_id . "'");
    }
}