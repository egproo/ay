<?php
namespace Opencart\Admin\Model\Migration;

class Validation extends \Opencart\System\Engine\Model {
    public function validateProduct($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Product name is required';
        }
        
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors[] = 'Invalid product price';
        }
        
        if (isset($data['quantity']) && !is_numeric($data['quantity'])) {
            $errors[] = 'Invalid product quantity';
        }
        
        return [
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        ];
    }
    
    public function validateCustomer($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Customer name is required';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($data['telephone']) && !preg_match('/^[0-9+\-\s()]*$/', $data['telephone'])) {
            $errors[] = 'Invalid telephone format';
        }
        
        return [
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        ];
    }
    
    public function validateOrder($data) {
        $errors = [];
        
        if (empty($data['customer_id'])) {
            $errors[] = 'Customer ID is required';
        }
        
        if (empty($data['products'])) {
            $errors[] = 'Order must contain at least one product';
        }
        
        if (!isset($data['total']) || $data['total'] < 0) {
            $errors[] = 'Invalid order total';
        }
        
        return [
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        ];
    }
    
    public function validateSupplier($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Supplier name is required';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($data['telephone']) && !preg_match('/^[0-9+\-\s()]*$/', $data['telephone'])) {
            $errors[] = 'Invalid telephone format';
        }
        
        return [
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        ];
    }
    
    public function validateInvoice($data) {
        $errors = [];
        
        if (empty($data['order_id'])) {
            $errors[] = 'Order ID is required';
        }
        
        if (!isset($data['total']) || $data['total'] < 0) {
            $errors[] = 'Invalid invoice total';
        }
        
        if (empty($data['date'])) {
            $errors[] = 'Invoice date is required';
        }
        
        return [
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        ];
    }
}