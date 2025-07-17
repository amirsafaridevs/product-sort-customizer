/**
 * Admin JavaScript for Category Product Sorter
 *
 * @package CategoryProductSorter
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var ASDWPSorterAdmin = {
        init: function() {
            this.asd_initTabs();
            this.asd_initSortable();
            this.asd_initEventHandlers();
            this.asd_initSearch();
        },

        asd_initTabs: function() {
            $('.asd-wpsorter-tab-button').on('click', function(e) {
                e.preventDefault();
                var tabId = $(this).data('tab');
                
                // Update active tab button
                $('.asd-wpsorter-tab-button').removeClass('active');
                $(this).addClass('active');
                
                // Update active tab content
                $('.asd-wpsorter-tab-content').removeClass('active');
                $('#' + tabId + '-tab').addClass('active');
            });
        },

        asd_initSortable: function() {
            $('#asd-wpsorter-product-list').sortable({
                handle: '.asd-wpsorter-drag-handle',
                placeholder: 'asd-wpsorter-product-row ui-sortable-placeholder',
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                update: function(event, ui) {
                    ASDWPSorterAdmin.asd_updatePositions();
                }
            });
        },

        asd_initEventHandlers: function() {
            $('#asd-wpsorter-save-order').on('click', function(e) {
                e.preventDefault();
                ASDWPSorterAdmin.asd_saveOrder();
            });

            $('#asd-wpsorter-reset-order').on('click', function(e) {
                e.preventDefault();
                ASDWPSorterAdmin.asd_resetOrder();
            });
        },

        asd_initSearch: function() {
            $('#asd-wpsorter-product-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                $('.asd-wpsorter-product-row').each(function() {
                    var productName = $(this).find('.asd-wpsorter-name-cell').text().toLowerCase();
                    var productId = $(this).data('product-id').toString();
                    
                    if (productName.includes(searchTerm) || productId.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        },

        asd_updatePositions: function() {
            $('.asd-wpsorter-product-row:visible').each(function(index) {
                var $row = $(this);
                var $positionDisplay = $row.find('.asd-wpsorter-position-display');
                $positionDisplay.text(index + 1);
                $row.attr('data-position', index + 1);
            });
        },

        asd_saveOrder: function() {
            var $status = $('#asd-wpsorter-status');
            var $saveButton = $('#asd-wpsorter-save-order');
            var originalText = $saveButton.text();

            // Show saving status
            $status.text(asd_wpsorter_ajax.strings.saving).removeClass('error success').addClass('saving');
            $saveButton.prop('disabled', true).text(asd_wpsorter_ajax.strings.saving);

            // Get product order
            var productOrder = [];
            $('.asd-wpsorter-product-row:visible').each(function() {
                productOrder.push($(this).data('product-id'));
            });

            // Get term ID and taxonomy from URL
            var urlParams = new URLSearchParams(window.location.search);
            var termId = urlParams.get('term_id');
            var taxonomy = urlParams.get('taxonomy') || 'product_cat';

            // Send AJAX request
            $.ajax({
                url: asd_wpsorter_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'asd_wpsorter_save_order',
                    nonce: asd_wpsorter_ajax.nonce,
                    term_id: termId,
                    taxonomy: taxonomy,
                    product_order: productOrder
                },
                success: function(response) {
                    if (response.success) {
                        $status.text(asd_wpsorter_ajax.strings.saved).removeClass('saving error').addClass('success');
                        setTimeout(function() {
                            $status.text('').removeClass('success');
                        }, 3000);
                    } else {
                        $status.text(response.data || asd_wpsorter_ajax.strings.error).removeClass('saving success').addClass('error');
                    }
                },
                error: function() {
                    $status.text(asd_wpsorter_ajax.strings.error).removeClass('saving success').addClass('error');
                },
                complete: function() {
                    $saveButton.prop('disabled', false).text(originalText);
                }
            });
        },

        asd_resetOrder: function() {
            if (!confirm(asd_wpsorter_ajax.strings.confirm_reset)) {
                return;
            }

            var $status = $('#asd-wpsorter-status');
            var $resetButton = $('#asd-wpsorter-reset-order');
            var originalText = $resetButton.text();

            // Show resetting status
            $status.text('Resetting...').removeClass('error success').addClass('saving');
            $resetButton.prop('disabled', true).text('Resetting...');

            // Get term ID and taxonomy from URL
            var urlParams = new URLSearchParams(window.location.search);
            var termId = urlParams.get('term_id');
            var taxonomy = urlParams.get('taxonomy') || 'product_cat';

            // Send AJAX request to reset order
            $.ajax({
                url: asd_wpsorter_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'asd_wpsorter_save_order',
                    nonce: asd_wpsorter_ajax.nonce,
                    term_id: termId,
                    taxonomy: taxonomy,
                    product_order: [],
                    reset: true
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show default order
                        location.reload();
                    } else {
                        $status.text(response.data || 'Error resetting order.').removeClass('saving success').addClass('error');
                    }
                },
                error: function() {
                    $status.text('Error resetting order.').removeClass('saving success').addClass('error');
                },
                complete: function() {
                    $resetButton.prop('disabled', false).text(originalText);
                }
            });
        },

        // Utility function to show notification
        asd_showNotification: function(message, type) {
            var $notification = $('<div class="asd-wpsorter-notification asd-wpsorter-notification-' + type + '">' + message + '</div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ASDWPSorterAdmin.init();
    });

})(jQuery); 