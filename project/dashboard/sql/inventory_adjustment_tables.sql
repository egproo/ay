-- Tabla de ajustes de inventario
CREATE TABLE IF NOT EXISTS `oc_inventory_adjustment` (
  `adjustment_id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(50) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `adjustment_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reason` varchar(255) NOT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`adjustment_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`),
  KEY `adjustment_date` (`adjustment_date`),
  KEY `reference_number` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabla de elementos de ajuste de inventario
CREATE TABLE IF NOT EXISTS `oc_inventory_adjustment_item` (
  `adjustment_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `adjustment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `adjustment_type` varchar(20) NOT NULL DEFAULT 'quantity',
  `quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`adjustment_item_id`),
  KEY `adjustment_id` (`adjustment_id`),
  KEY `product_id` (`product_id`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
