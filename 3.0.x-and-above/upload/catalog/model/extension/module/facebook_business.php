<?php
/**
  * Copyright (c) Facebook, Inc. and its affiliates.
  * All rights reserved.
  *
  * This source code is licensed under the license found in the
  * LICENSE file in the root directory of this source tree.
  */

require_once(DIR_SYSTEM . 'library/vendor/facebook_business/vendor/autoload.php');

use FacebookAds\Api;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\AdsPixelSettings;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequestAsync;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\Util;

class ModelExtensionModuleFacebookBusiness extends Model {
    private $pluginVersion = '4.2.1';

    /** 
      * This function is a direct lifting from admin/model/catalog/product.php,
      * except that this SQL query is joining other tables to obtain
      * brand, category, Facebook Product ID and Facebook Product Group ID.
      * The rationale to duplicate this method into an external class instead
      * of modifying the existing getProducts metohd is to prevent any
      * potential conflicts with other third-party extensions.
      */
    public function getProducts($data = array()) {
        $sql = "SELECT p.*, pd.*, m.name AS manufacturer_name, ptc.category_name FROM " . DB_PREFIX . "product p " .
          "LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) " .
          "LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) " .
          "LEFT JOIN " . DB_PREFIX . "product_special ps ON (p.product_id = ps.product_id) " .
          "LEFT JOIN " .
            "(SELECT ptc.product_id, ptc.category_id, cd.name AS category_name " .
              "FROM (SELECT product_id, MAX(category_id) AS category_id " .
                "FROM " . DB_PREFIX . "product_to_category " .
                "GROUP BY product_id) AS ptc " .
              "LEFT JOIN " . DB_PREFIX . "category_description cd " .
                "ON (ptc.category_id = cd.category_id)) ptc " .
            "ON (p.product_id = ptc.product_id) " .
          "WHERE pd.language_id = '" .
            (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
            $sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
            $sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (isset($data['filter_image']) && !is_null($data['filter_image'])) {
            if ($data['filter_image'] == 1) {
                $sql .= " AND (p.image IS NOT NULL AND p.image <> '' AND p.image <> 'no_image.png')";
            } else {
                $sql .= " AND (p.image IS NULL OR p.image = '' OR p.image = 'no_image.png')";
            }
        }

        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
            'p.status',
            'p.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
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

    public function getProductSpecials($product_id) {
        $query = $this->db->query("SELECT price AS special, date_start AS special_date_start, date_end AS special_date_end FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

        return $query->row;
    }
