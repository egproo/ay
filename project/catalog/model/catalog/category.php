<?php
class ModelCatalogCategory extends Model {
	public function getCategory($category_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row;
	}

	public function getCategories($parent_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");

		return $query->rows;
	}
public function getSearchFilters($search) {
    $filter_group_data = [];

    // جمع معرفات المنتجات التي تطابق كلمة البحث
    $product_ids_query = $this->db->query("SELECT DISTINCT p.product_id 
                                           FROM " . DB_PREFIX . "product p 
                                           LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                                           LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
                                           WHERE p.status = '1' 
                                           AND p.date_available <= NOW() 
                                           AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
                                           AND pd.name LIKE '%" . $this->db->escape($search) . "%'");
    
    $product_ids = array_column($product_ids_query->rows, 'product_id');
    
    if (empty($product_ids)) {
        return $filter_group_data; // Return empty if no products found
    }

    $product_ids_string = implode(',', $product_ids);

    // تصحيح استعلام الجمع بين filter_group و filter_group_description
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "filter_group fg 
                               LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) 
                               WHERE fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                               ORDER BY fg.sort_order, fgd.name");

    foreach ($query->rows as $group) {
        $filters = [];

        // استعلام لجلب الفلاتر التي تنتمي إلى كل مجموعة فلاتر والمرتبطة بالمنتجات الحالية
        $filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name 
                                          FROM " . DB_PREFIX . "filter f 
                                          LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) 
                                          LEFT JOIN " . DB_PREFIX . "product_filter pf ON (f.filter_id = pf.filter_id) 
                                          WHERE f.filter_group_id = '" . (int)$group['filter_group_id'] . "' 
                                          AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                          AND pf.product_id IN (" . $product_ids_string . ") 
                                          ORDER BY f.sort_order, fd.name");

        foreach ($filter_query->rows as $filter) {
            $filters[] = [
                'filter_id' => $filter['filter_id'],
                'name'      => $filter['name']
            ];
        }

        // إضافة مجموعة الفلاتر فقط إذا كانت تحتوي على فلاتر
        if (!empty($filters)) {
            $filter_group_data[] = [
                'filter_group_id' => $group['filter_group_id'],
                'name'            => $group['name'],
                'filter'          => $filters
            ];
        }
    }

    return $filter_group_data;
}

	
public function getCatalogFilters() {
    $filter_group_data = [];

    // جمع معرفات المنتجات الحالية في الكتالوج
    $product_ids_query = $this->db->query("SELECT DISTINCT p.product_id FROM " . DB_PREFIX . "product p 
                                           LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                                           LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
                                           WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
    
    $product_ids = array_column($product_ids_query->rows, 'product_id');
    
    if (empty($product_ids)) {
        return $filter_group_data; // Return empty if no products found
    }

    $product_ids_string = implode(',', $product_ids);

    // تصحيح استعلام الجمع بين filter_group و filter_group_description
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "filter_group fg 
                               LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) 
                               WHERE fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                               ORDER BY fg.sort_order, fgd.name");

    foreach ($query->rows as $group) {
        $filters = [];

        // استعلام لجلب الفلاتر التي تنتمي إلى كل مجموعة فلاتر والمرتبطة بالمنتجات الحالية
        $filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name 
                                          FROM " . DB_PREFIX . "filter f 
                                          LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) 
                                          LEFT JOIN " . DB_PREFIX . "product_filter pf ON (f.filter_id = pf.filter_id) 
                                          WHERE f.filter_group_id = '" . (int)$group['filter_group_id'] . "' 
                                          AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                          AND pf.product_id IN (" . $product_ids_string . ") 
                                          ORDER BY f.sort_order, fd.name");

        foreach ($filter_query->rows as $filter) {
            $filters[] = [
                'filter_id' => $filter['filter_id'],
                'name'      => $filter['name']
            ];
        }

        // إضافة مجموعة الفلاتر فقط إذا كانت تحتوي على فلاتر
        if (!empty($filters)) {
            $filter_group_data[] = [
                'filter_group_id' => $group['filter_group_id'],
                'name'            => $group['name'],
                'filter'          => $filters
            ];
        }
    }

    return $filter_group_data;
}


public function getCategoryFilters($category_id) {
    $filter_group_data = [];

    // Get product IDs for the specific category
    $product_ids_query = $this->db->query("SELECT DISTINCT p.product_id 
                                           FROM " . DB_PREFIX . "product p 
                                           LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                                           LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
                                           WHERE p.status = '1' 
                                           AND p.date_available <= NOW() 
                                           AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
                                           AND p2c.category_id = '" . (int)$category_id . "'");

    $product_ids = array_column($product_ids_query->rows, 'product_id');
    
    if (empty($product_ids)) {
        return $filter_group_data; // Return empty if no products found
    }

    $product_ids_string = implode(',', $product_ids);

    // Get filter groups
    $filter_group_query = $this->db->query("SELECT DISTINCT fg.filter_group_id, fgd.name, fg.sort_order 
                                            FROM " . DB_PREFIX . "filter_group fg 
                                            LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) 
                                            WHERE fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                            ORDER BY fg.sort_order, LCASE(fgd.name)");

    foreach ($filter_group_query->rows as $filter_group) {
        $filter_data = [];

        // Get filters for each group that are associated with products in this category
        $filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name 
                                          FROM " . DB_PREFIX . "filter f 
                                          LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) 
                                          LEFT JOIN " . DB_PREFIX . "product_filter pf ON (f.filter_id = pf.filter_id) 
                                          WHERE f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' 
                                          AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                          AND pf.product_id IN (" . $product_ids_string . ") 
                                          ORDER BY f.sort_order, LCASE(fd.name)");

        foreach ($filter_query->rows as $filter) {
            $filter_data[] = [
                'filter_id' => $filter['filter_id'],
                'name'      => $filter['name']
            ];
        }

        if ($filter_data) {
            $filter_group_data[] = [
                'filter_group_id' => $filter_group['filter_group_id'],
                'name'            => $filter_group['name'],
                'filter'          => $filter_data
            ];
        }
    }

    return $filter_group_data;
}

	public function getCategoryLayoutId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}
}