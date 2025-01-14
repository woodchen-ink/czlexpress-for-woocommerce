jQuery(document).ready(function($) {
    // 创建运单按钮点击事件
    $('.czl-create-shipment').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var order_id = button.data('order-id');
        
        button.prop('disabled', true).text(czl_ajax.creating_text);
        
        $.ajax({
            url: czl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'czl_create_shipment',
                order_id: order_id,
                nonce: czl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.text(czl_ajax.success_text);
                    location.reload();
                } else {
                    button.text(czl_ajax.error_text);
                    alert(response.data);
                }
            },
            error: function() {
                button.text(czl_ajax.error_text);
                alert('请求失败，请重试');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
    
    // 更新跟踪单号对话框
    var trackingDialog = $('<div id="czl-tracking-dialog" title="更新跟踪单号">' +
        '<p>请输入新的跟踪单号：</p>' +
        '<input type="text" id="czl-tracking-number" style="width: 100%;">' +
        '</div>').dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        buttons: {
            "更新": function() {
                var dialog = $(this);
                var button = dialog.data('button');
                var order_id = button.data('order-id');
                var tracking_number = $('#czl-tracking-number').val();
                
                if (!tracking_number) {
                    alert('请输入跟踪单号');
                    return;
                }
                
                $.ajax({
                    url: czl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'czl_update_tracking_number',
                        order_id: order_id,
                        tracking_number: tracking_number,
                        nonce: czl_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('请求失败，请重试');
                    }
                });
            },
            "取消": function() {
                $(this).dialog('close');
            }
        }
    });
    
    // 更新跟踪单号按钮点击事件
    $('.czl-update-tracking').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var currentTracking = button.data('tracking');
        
        $('#czl-tracking-number').val(currentTracking || '');
        trackingDialog.data('button', button).dialog('open');
    });
    
    // 更新轨迹信息按钮点击事件
    $('.czl-update-tracking-info').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var order_id = button.data('order-id');
        
        button.prop('disabled', true).text('更新中...');
        
        $.ajax({
            url: czl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'czl_update_tracking_info',
                order_id: order_id,
                nonce: czl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('轨迹更新成功');
                    location.reload();
                } else {
                    alert(response.data.message || '更新失败');
                }
            },
            error: function() {
                alert('请求失败，请重试');
            },
            complete: function() {
                button.prop('disabled', false).text('更新轨迹');
            }
        });
    });
}); 