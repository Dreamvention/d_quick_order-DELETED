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
            product_link = '" . $this->db->escape($data['product_link']) . "', 
            product_price = '" . $this->db->escape($data['product_price']) . "',
            product_amount = '" . (int)$this->db->escape($data['product_amount']) . "',  
            customer_session_id = '" . $this->db->escape($data['customer_session_id']) . "', 
            customer_name = '" . $this->db->escape($data['customer_name']) . "', 
            customer_email = '" . $this->db->escape($data['customer_email']) . "', 
            customer_phone = '" . $this->db->escape($data['customer_phone']) . "', 
            customer_comment = '" . $this->db->escape($data['customer_comment']) . "', 
            status = 'open', 
            date_added = NOW(), 
            date_modified = NOW();");
    }

    public function getOrderById($id)
    {
        $query = $this->db->query("SELECT id  FROM " . DB_PREFIX . "$this->tableName WHERE id = '". $id ."' LIMIT 1");

        return $query->row;
    }

    public function getSetting() {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "d_qo_settings` LIMIT 1")->rows;
    }

    public function setSetting($configSettings)
    {
        return $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "d_qo_settings SET 
            d_quick_order_enabled = '" . boolval($configSettings['d_quick_order_enabled']) . "', 
            d_quick_order = '" . $this->db->escape(strval($configSettings['d_quick_order'])) . "', 
            d_qo_button_text = '" . $this->db->escape(strval($configSettings['d_qo_button_text'])) . "', 
            d_qo_button_class = '" . $this->db->escape(strval($configSettings['d_qo_button_class'])) . "', 
            d_qo_modal_heading = '" . $this->db->escape(strval($configSettings['d_qo_modal_heading'])) . "', 
            d_qo_modal_description_text = '" . $this->db->escape(strval($configSettings['d_qo_modal_description_text'])) . "', 
            d_qo_modal_phone_format = '" . $this->db->escape(strval($configSettings['d_qo_modal_phone_format'])) . "', 
            d_qo_modal_comment_enable = '" . boolval($configSettings['d_qo_modal_comment_enable']) ."', 
            d_qo_modal_button_text = '" . $this->db->escape(strval($configSettings['d_qo_modal_button_text'])) . "', 
            date_added = NOW(), 
            date_modified = NOW();");
    }
}