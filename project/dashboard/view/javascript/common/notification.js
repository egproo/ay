/**
 * Dashboard Notification System
 * Handles real-time notifications and messaging using Socket.IO
 */

var NotificationCenter = (function() {
    'use strict';
    
    // Configuration
    var config = {
        refreshInterval: 30000, // 30 seconds default refresh
        maxNotifications: 50,
        notificationSound: true,
        desktopNotifications: true,
        animationEnabled: true
    };
    
    // State variables
    var socket = null;
    var notificationCount = 0;
    var messageCount = 0;
    var refreshTimer = null;
    var userToken = '';
    var notificationTypes = ['system', 'order', 'customer', 'alert', 'message'];
    var activeNotifications = [];
    var activeMessages = [];
    
    // DOM Elements
    var $notificationBadge = null;
    var $messageBadge = null;
    var $notificationList = null;
    var $messageList = null;
    var $notificationCenter = null;
    var $messageCenter = null;
    
    /**
     * Initialize the notification system
     * @param {Object} options Configuration options
     */
    function init(options) {
        // Merge options with default config
        if (options) {
            config = Object.assign(config, options);
        }
        
        // Store user token
        userToken = options.userToken || '';
        
        // Initialize DOM elements
        $notificationBadge = $('.notification-badge');
        $messageBadge = $('.message-badge');
        $notificationList = $('.notification-list');
        $messageList = $('.message-list');
        $notificationCenter = $('.notification-center');
        $messageCenter = $('.message-center');
        
        // Initialize Socket.IO if available
        initializeSocketIO();
        
        // Bind event handlers
        bindEvents();
        
        // Initial data load
        loadNotifications();
        loadMessages();
        
        // Start refresh timer
        startRefreshTimer();
        
        // Request permission for desktop notifications
        if (config.desktopNotifications) {
            requestNotificationPermission();
        }
    }
    
    /**
     * Initialize Socket.IO connection for real-time updates
     */
    function initializeSocketIO() {
        if (typeof io !== 'undefined') {
            try {
                socket = io(window.location.origin, {
                    path: '/socket.io',
                    transports: ['websocket', 'polling'],
                    query: {
                        'user_token': userToken
                    }
                });
                
                // Socket.IO event handlers
                socket.on('connect', function() {
                    console.log('Socket.IO connected');
                });
                
                socket.on('notification', function(data) {
                    handleNewNotification(data);
                });
                
                socket.on('message', function(data) {
                    handleNewMessage(data);
                });
                
                socket.on('disconnect', function() {
                    console.log('Socket.IO disconnected');
                    // Fall back to polling when socket disconnects
                    startRefreshTimer();
                });
                
                socket.on('connect_error', function(error) {
                    console.error('Socket.IO connection error:', error);
                    // Fall back to polling on connection error
                    startRefreshTimer();
                });
            } catch (e) {
                console.error('Failed to initialize Socket.IO:', e);
                // Fall back to polling if Socket.IO fails
                startRefreshTimer();
            }
        } else {
            console.log('Socket.IO not available, using polling');
            startRefreshTimer();
        }
    }
    
    /**
     * Bind event handlers for notification interactions
     */
    function bindEvents() {
        // Toggle notification center
        $(document).on('click', '.notification-toggle', function(e) {
            e.preventDefault();
            toggleNotificationCenter();
        });
        
        // Toggle message center
        $(document).on('click', '.message-toggle', function(e) {
            e.preventDefault();
            toggleMessageCenter();
        });
        
        // Mark notification as read
        $(document).on('click', '.notification-item', function() {
            var notificationId = $(this).data('id');
            markNotificationAsRead(notificationId);
        });
        
        // Mark message as read
        $(document).on('click', '.message-item', function() {
            var messageId = $(this).data('id');
            markMessageAsRead(messageId);
        });
        
        // Mark all notifications as read
        $(document).on('click', '.mark-all-read', function(e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
        
        // Notification tab switching
        $(document).on('click', '.notification-tab', function() {
            var type = $(this).data('type');
            $('.notification-tab').removeClass('active');
            $(this).addClass('active');
            filterNotifications(type);
        });
        
        // Close notification center when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.notification-center, .notification-toggle').length) {
                $notificationCenter.removeClass('show');
            }
            if (!$(e.target).closest('.message-center, .message-toggle').length) {
                $messageCenter.removeClass('show');
            }
        });
    }
    
    /**
     * Load notifications from the server
     */
    function loadNotifications() {
        $.ajax({
            url: 'index.php?route=common/notification&user_token=' + userToken,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.notifications) {
                    activeNotifications = response.notifications;
                    renderNotifications(response.notifications);
                    updateNotificationBadge(response.unread_count);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load notifications:', error);
            }
        });
    }
    
    /**
     * Load messages from the server
     */
    function loadMessages() {
        $.ajax({
            url: 'index.php?route=common/message&user_token=' + userToken,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.messages) {
                    activeMessages = response.messages;
                    renderMessages(response.messages);
                    updateMessageBadge(response.unread_count);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load messages:', error);
            }
        });
    }
    
    /**
     * Render notifications in the notification center
     * @param {Array} notifications Array of notification objects
     */
    function renderNotifications(notifications) {
        if (!$notificationList.length) return;
        
        $notificationList.empty();
        
        if (notifications.length === 0) {
            $notificationList.append('<li class="notification-item empty"><div class="notification-details"><span class="notification-message">No notifications</span></div></li>');
            return;
        }
        
        notifications.forEach(function(notification) {
            var unreadClass = notification.is_read ? '' : 'unread';
            var iconColor = notification.color || '#007bff';
            
            var html = '<li class="notification-item ' + unreadClass + '" data-id="' + notification.notification_id + '" data-type="' + notification.reference_type + '">' +
                      '<div class="notification-icon" style="color: ' + iconColor + '; background-color: ' + iconColor + '10;">' +
                      '<i class="' + (notification.icon || 'fas fa-bell') + '"></i>' +
                      '</div>' +
                      '<div class="notification-details">' +
                      '<h5 class="notification-title">' + notification.title + '</h5>' +
                      '<p class="notification-message">' + notification.message + '</p>' +
                      '<span class="notification-time">' + notification.relative_time + '</span>' +
                      '</div>' +
                      '</li>';
            
            $notificationList.append(html);
        });
    }
    
    /**
     * Render messages in the message center
     * @param {Array} messages Array of message objects
     */
    function renderMessages(messages) {
        if (!$messageList.length) return;
        
        $messageList.empty();
        
        if (messages.length === 0) {
            $messageList.append('<li class="message-item empty"><div class="message-details"><span class="message-preview">No messages</span></div></li>');
            return;
        }
        
        messages.forEach(function(message) {
            var unreadClass = message.is_read ? '' : 'unread';
            var avatarUrl = message.sender_image || 'view/image/user.png';
            
            var html = '<li class="message-item ' + unreadClass + '" data-id="' + message.message_id + '">' +
                      '<div class="message-avatar">' +
                      '<img src="' + avatarUrl + '" alt="' + message.sender_name + '">' +
                      '</div>' +
                      '<div class="message-details">' +
                      '<div class="message-sender">' +
                      message.sender_name +
                      '<span class="message-time">' + message.relative_time + '</span>' +
                      '</div>' +
                      '<p class="message-preview">' + message.subject + '</p>' +
                      '</div>' +
                      '</li>';
            
            $messageList.append(html);
        });
    }
    
    /**
     * Update the notification badge count
     * @param {Number} count Number of unread notifications
     */
    function updateNotificationBadge(count) {
        notificationCount = count;
        
        if (!$notificationBadge.length) return;
        
        if (count > 0) {
            $notificationBadge.text(count > 99 ? '99+' : count).show();
        } else {
            $notificationBadge.hide();
        }
    }
    
    /**
     * Update the message badge count
     * @param {Number} count Number of unread messages
     */
    function updateMessageBadge(count) {
        messageCount = count;
        
        if (!$messageBadge.length) return;
        
        if (count > 0) {
            $messageBadge.text(count > 99 ? '99+' : count).show();
        } else {
            $messageBadge.hide();
        }
    }
    
    /**
     * Toggle the notification center visibility
     */
    function toggleNotificationCenter() {
        $notificationCenter.toggleClass('show');
        $messageCenter.removeClass('show');
    }
    
    /**
     * Toggle the message center visibility
     */
    function toggleMessageCenter() {
        $messageCenter.toggleClass('show');
        $notificationCenter.removeClass('show');
    }
    
    /**
     * Mark a notification as read
     * @param {Number} notificationId ID of the notification to mark as read
     */
    function markNotificationAsRead(notificationId) {
        $.ajax({
            url: 'index.php?route=common/notification/markAsRead&user_token=' + userToken,
            type: 'POST',
            data: {
                notification_id: notificationId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update UI
                    $('.notification-item[data-id="' + notificationId + '"]').removeClass('unread');
                    updateNotificationBadge(response.unread_count);
                }
            }
        });
    }
    
    /**
     * Mark a message as read
     * @param {Number} messageId ID of the message to mark as read
     */
    function markMessageAsRead(messageId) {
        $.ajax({
            url: 'index.php?route=common/message/markAsRead&user_token=' + userToken,
            type: 'POST',
            data: {
                message_id: messageId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update UI
                    $('.message-item[data-id="' + messageId + '"]').removeClass('unread');
                    updateMessageBadge(response.unread_count);
                }
            }
        });
    }
    
    /**
     * Mark all notifications as read
     */
    function markAllNotificationsAsRead() {
        $.ajax({
            url: 'index.php?route=common/notification/markAllAsRead&user_token=' + userToken,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update UI
                    $('.notification-item').removeClass('unread');
                    updateNotificationBadge(0);
                }
            }
        });
    }
    
    /**
     * Filter notifications by type
     * @param {String} type Type of notifications to show
     */
    function filterNotifications(type) {
        if (type === 'all') {
            $('.notification-item').show();
            return;
        }
        
        $('.notification-item').hide();
        $('.notification-item[data-type="' + type + '"]').show();
    }
    
    /**
     * Handle a new notification received via Socket.IO
     * @param {Object} notification Notification object
     */
    function handleNewNotification(notification) {
        // Add to active notifications
        activeNotifications.unshift(notification);
        
        // Trim to max notifications
        if (activeNotifications.length > config.maxNotifications) {
            activeNotifications = activeNotifications.slice(0, config.maxNotifications);
        }
        
        // Re-render notifications
        renderNotifications(activeNotifications);
        
        // Update badge
        updateNotificationBadge(notificationCount + 1);
        
        // Show desktop notification if enabled
        if (config.desktopNotifications) {
            showDesktopNotification(notification);
        }
        
        // Play sound if enabled
        if (config.notificationSound) {
            playNotificationSound();
        }
    }
    
    /**
     * Handle a new message received via Socket.IO
     * @param {Object} message Message object
     */
    function handleNewMessage(message) {
        // Add to active messages
        activeMessages.unshift(message);
        
        // Trim to max messages
        if (activeMessages.length > config.maxNotifications) {
            activeMessages = activeMessages.slice(0, config.maxNotifications);
        }
        
        // Re-render messages
        renderMessages(activeMessages);
        
        // Update badge
        updateMessageBadge(messageCount + 1);
        
        // Show desktop notification if enabled
        if (config.desktopNotifications) {
            showDesktopNotification({
                title: 'New Message from ' + message.sender_name,
                message: message.subject,
                icon: 'fas fa-envelope'
            });
        }
        
        // Play sound if enabled
        if (config.notificationSound) {
            playNotificationSound();
        }
    }
    
    /**
     * Show a desktop notification
     * @param {Object} notification Notification object
     */
    function showDesktopNotification(notification) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        
        var options = {
            body: notification.message,
            icon: 'view/image/logo.png'
        };
        
        var desktopNotification = new Notification(notification.title, options);
        
        // Auto close after 5 seconds
        setTimeout(function() {
            desktopNotification.close();
        }, 5000);
    }
    
    /**
     * Request permission for desktop notifications
     */
    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('This browser does not support desktop notifications');
            return;
        }
        
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    }
    
    /**
     * Play notification sound
     */
    function playNotificationSound() {
        // Create audio element if it doesn't exist
        var audio = document.getElementById('notification-sound');
        if (!audio) {
            audio = document.createElement('audio');
            audio.id = 'notification-sound';
            audio.src = 'view/javascript/notification-sound.mp3';
            audio.volume = 0.5;
            document.body.appendChild(audio);
        }
        
        // Play sound
        audio.play().catch(function(e) {
            console.log('Failed to play notification sound:', e);
        });
    }
    
    /**
     * Start the refresh timer for polling updates
     */
    function startRefreshTimer() {
        // Clear existing timer
        if (refreshTimer) {
            clearInterval(refreshTimer);
        }
        
        // Only start timer if interval is greater than 0
        if (config.refreshInterval > 0) {
            refreshTimer = setInterval(function() {
                loadNotifications();
                loadMessages();
            }, config.refreshInterval);
        }
    }
    
    /**
     * Stop the refresh timer
     */
    function stopRefreshTimer() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }
    
    /**
     * Update configuration settings
     * @param {Object} newConfig New configuration settings
     */
    function updateConfig(newConfig) {
        config = Object.assign(config, newConfig);
        
        // Update refresh timer if interval changed
        if (newConfig.hasOwnProperty('refreshInterval')) {
            startRefreshTimer();
        }
    }
    
    // Public API
    return {
        init: init,
        loadNotifications: loadNotifications,
        loadMessages: loadMessages,
        markAllNotificationsAsRead: markAllNotificationsAsRead,
        updateConfig: updateConfig
    };
})();

// Initialize when document is ready
$(document).ready(function() {
    // Initialize notification center with user token
    NotificationCenter.init({
        userToken: user_token,
        refreshInterval: 30000, // 30 seconds
        desktopNotifications: true,
        notificationSound: true
    });
});