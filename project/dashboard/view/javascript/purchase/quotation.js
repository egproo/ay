/**
 * Show price history modal and load data
 * @param {number} productId - Product ID
 * @param {number} unitId - Unit ID
 */
function showPriceHistory(productId, unitId) {
    $('#modal-price-history').modal('show');
    loadPriceHistory(productId, unitId);
}

/**
 * Show supplier performance modal and load data
 * @param {number} productId - Product ID
 * @param {number} supplierId - Supplier ID
 */
function showSupplierPerformance(productId, supplierId) {
    $('#modal-supplier-performance').modal('show');
    loadSupplierHistory(productId, supplierId);
}

/**
 * Initialize product row event handlers
 * @param {jQuery} $row - jQuery object for the product row
 */
function initializeProductRow($row) {
    // Add price history button
    $row.find('.product-details').append(
        '<button type="button" class="btn btn-info btn-sm mt-2 price-history-btn">' +
        '<i class="fa fa-line-chart"></i> Price History</button>'
    );

    // Add supplier performance button if supplier is selected
    if ($('#supplier-id').val()) {
        $row.find('.product-details').append(
            '<button type="button" class="btn btn-primary btn-sm mt-2 ml-2 supplier-performance-btn">' +
            '<i class="fa fa-bar-chart"></i> Supplier Performance</button>'
        );
    }

    // Bind click handlers
    $row.find('.price-history-btn').on('click', function() {
        var productId = $row.find('[name="item[product_id][]"]').val();
        var unitId = $row.find('[name="item[unit_id][]"]').val();
        showPriceHistory(productId, unitId);
    });

    $row.find('.supplier-performance-btn').on('click', function() {
        var productId = $row.find('[name="item[product_id][]"]').val();
        var supplierId = $('#supplier-id').val();
        showSupplierPerformance(productId, supplierId);
    });
}

// Add to existing document ready handler
$(document).ready(function() {
    // Initialize existing rows
    $('#item-container tr').each(function() {
        initializeProductRow($(this));
    });

    // Initialize new rows when added
    $('#add-item-btn').on('click', function() {
        var $newRow = $('#item-container tr:last');
        initializeProductRow($newRow);
    });

    // Update supplier performance buttons when supplier changes
    $('#supplier-id').on('change', function() {
        var supplierId = $(this).val();
        $('#item-container tr').each(function() {
            var $row = $(this);
            if (supplierId) {
                if (!$row.find('.supplier-performance-btn').length) {
                    $row.find('.product-details').append(
                        '<button type="button" class="btn btn-primary btn-sm mt-2 ml-2 supplier-performance-btn">' +
                        '<i class="fa fa-bar-chart"></i> Supplier Performance</button>'
                    );
                }
            } else {
                $row.find('.supplier-performance-btn').remove();
            }
        });
    });
});