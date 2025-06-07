/**
 * نظام أيم ERP: سكريبت المساعد الذكي
 * هذا الملف يوفر وظائف JavaScript للمساعد الذكي
 */

// تهيئة المساعد الذكي
var AiAssistant = {
    init: function() {
        // تهيئة الأحداث والوظائف
        this.setupEventListeners();
        this.setupAutoComplete();
        this.scrollToBottom();
    },
    
    // إعداد مستمعي الأحداث
    setupEventListeners: function() {
        // إرسال الرسالة عند الضغط على زر الإرسال أو مفتاح Enter
        $('#form-message').on('submit', function(e) {
            e.preventDefault();
            AiAssistant.sendMessage();
        });
        
        // مسح المحادثة
        $('#button-clear').on('click', function() {
            if (confirm(ai_assistant_text_confirm)) {
                AiAssistant.clearConversation();
            }
        });
        
        // إدراج اقتراح عند النقر عليه
        $('.suggestion-item').on('click', function() {
            var text = $(this).text().trim();
            $('#input-message').val(text);
            $('#input-message').focus();
        });
    },
    
    // إعداد الإكمال التلقائي
    setupAutoComplete: function() {
        if (ai_assistant_auto_complete) {
            // يمكن تنفيذ وظيفة الإكمال التلقائي هنا
            // مثال: استخدام مكتبة خارجية أو API للإكمال التلقائي
        }
    },
    
    // إرسال رسالة
    sendMessage: function() {
        var message = $('#input-message').val();
        
        if (message.trim() === '') {
            return;
        }
        
        // إضافة رسالة المستخدم إلى المحادثة
        this.addUserMessage(message);
        
        // مسح حقل الإدخال
        $('#input-message').val('');
        
        // إظهار مؤشر الكتابة
        $('#typing-indicator').show();
        
        // إرسال الرسالة إلى الخادم
        $.ajax({
            url: ai_assistant_send_url,
            type: 'POST',
            data: { message: message },
            dataType: 'json',
            success: function(json) {
                // إخفاء مؤشر الكتابة
                $('#typing-indicator').hide();
                
                if (json.error) {
                    // عرض رسالة الخطأ
                    AiAssistant.addAssistantMessage(json.error);
                } else if (json.response) {
                    // إضافة رد المساعد
                    AiAssistant.addAssistantMessage(json.response);
                }
            },
            error: function() {
                // إخفاء مؤشر الكتابة
                $('#typing-indicator').hide();
                
                // عرض رسالة خطأ
                AiAssistant.addAssistantMessage(ai_assistant_error_request);
            }
        });
    },
    
    // إضافة رسالة المستخدم
    addUserMessage: function(message) {
        var time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        var html = '<div class="message user">'
            + '<div class="message-content">'
            + '<div class="message-avatar"><i class="fa fa-user"></i></div>'
            + '<div class="message-text">' + this.escapeHtml(message) + '</div>'
            + '</div>'
            + '<div class="message-time">' + time + '</div>'
            + '</div>';
        
        $('#ai-chat-messages').append(html);
        this.scrollToBottom();
    },
    
    // إضافة رسالة المساعد
    addAssistantMessage: function(message) {
        var time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        var html = '<div class="message assistant">'
            + '<div class="message-content">'
            + '<div class="message-avatar"><i class="fa fa-robot"></i></div>'
            + '<div class="message-text">' + this.formatMessage(message) + '</div>'
            + '</div>'
            + '<div class="message-time">' + time + '</div>'
            + '</div>';
        
        $('#ai-chat-messages').append(html);
        this.scrollToBottom();
    },
    
    // تنسيق الرسالة (تحويل الروابط والرموز)
    formatMessage: function(message) {
        // تحويل الروابط إلى عناصر قابلة للنقر
        message = message.replace(/((https?|ftp):\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
        
        // تحويل النص المحاط بعلامات ** إلى نص عريض
        message = message.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // تحويل النص المحاط بعلامات * إلى نص مائل
        message = message.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        
        // تحويل أسطر جديدة إلى <br>
        message = message.replace(/\n/g, '<br>');
        
        return message;
    },
    
    // تمرير إلى أسفل منطقة المحادثة
    scrollToBottom: function() {
        var chatMessages = document.getElementById('ai-chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    },
    
    // مسح المحادثة
    clearConversation: function() {
        $.ajax({
            url: ai_assistant_clear_url,
            type: 'POST',
            dataType: 'json',
            success: function(json) {
                if (json.success) {
                    // مسح الرسائل
                    $('#ai-chat-messages').html('');
                    
                    // إضافة رسالة الترحيب
                    var time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    var html = '<div class="message assistant">'
                        + '<div class="message-content">'
                        + '<div class="message-avatar"><i class="fa fa-robot"></i></div>'
                        + '<div class="message-text">' + ai_assistant_welcome + '</div>'
                        + '</div>'
                        + '<div class="message-time">' + time + '</div>'
                        + '</div>';
                    
                    $('#ai-chat-messages').append(html);
                }
            }
        });
    },
    
    // تهرب من الرموز الخاصة في HTML
    escapeHtml: function(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
};

// تنفيذ عند اكتمال تحميل الصفحة
$(document).ready(function() {
    AiAssistant.init();
});