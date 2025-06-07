/**
 * Workflow Designer JavaScript
 */
$(document).ready(function() {
    // Initialize workflow designer
    initWorkflowDesigner();
});

function initWorkflowDesigner() {
    // Main workflow designer initialization
    console.log('Workflow Designer initialized');
    
    // Initialize draggable nodes
    initDraggableNodes();
    
    // Initialize connectors
    initConnectors();
    
    // Initialize toolbar
    initToolbar();
    
    // Initialize property panel
    initPropertyPanel();
    
    // Load workflow if ID is present
    var workflowId = $('#workflow-container').data('workflow-id');
    if (workflowId) {
        loadWorkflow(workflowId);
    }
}

function initDraggableNodes() {
    // Make node elements draggable
    $('.workflow-node').draggable({
        helper: 'clone',
        cursor: 'move',
        snap: '.workflow-canvas',
        snapMode: 'inner',
        stop: function(event, ui) {
            // Create node on canvas when dropped
            createNode(ui.helper, ui.position);
        }
    });
}

function initConnectors() {
    // Initialize connector lines between nodes
    $('.workflow-node-connector').on('mousedown', function(e) {
        startConnector($(this), e);
    });
}

function initToolbar() {
    // Initialize toolbar buttons
    $('#workflow-save').on('click', function() {
        saveWorkflow();
    });
    
    $('#workflow-new').on('click', function() {
        newWorkflow();
    });
    
    $('#workflow-delete').on('click', function() {
        deleteWorkflow();
    });
    
    $('#workflow-zoom-in').on('click', function() {
        zoomIn();
    });
    
    $('#workflow-zoom-out').on('click', function() {
        zoomOut();
    });
    
    $('#workflow-fit').on('click', function() {
        fitWorkflow();
    });
}

function initPropertyPanel() {
    // Initialize property panel
    $('.workflow-node').on('click', function() {
        showProperties($(this).data('node-id'));
    });
    
    $('#property-panel-close').on('click', function() {
        hideProperties();
    });
    
    $('#property-panel-apply').on('click', function() {
        applyProperties();
    });
}

function createNode(node, position) {
    // Create a new node on the canvas
    var nodeType = node.data('node-type');
    var nodeId = 'node-' + Math.floor(Math.random() * 10000);
    
    var newNode = $('<div>', {
        'class': 'workflow-node workflow-node-' + nodeType,
        'id': nodeId,
        'data-node-type': nodeType,
        'data-node-id': nodeId
    }).css({
        'left': position.left,
        'top': position.top
    }).append(
        $('<div>', {
            'class': 'workflow-node-title'
        }).text(nodeType.charAt(0).toUpperCase() + nodeType.slice(1))
    ).append(
        $('<div>', {
            'class': 'workflow-node-connectors'
        }).append(
            $('<div>', {
                'class': 'workflow-node-connector workflow-node-input',
                'data-connector-type': 'input'
            })
        ).append(
            $('<div>', {
                'class': 'workflow-node-connector workflow-node-output',
                'data-connector-type': 'output'
            })
        )
    );
    
    $('.workflow-canvas').append(newNode);
    
    // Make the new node draggable
    newNode.draggable({
        containment: '.workflow-canvas',
        stack: '.workflow-node',
        stop: function(event, ui) {
            // Update connections when node is moved
            updateConnections(nodeId);
        }
    });
    
    // Make node selectable
    newNode.on('click', function() {
        selectNode(nodeId);
    });
    
    // Initialize connectors for the new node
    newNode.find('.workflow-node-connector').on('mousedown', function(e) {
        startConnector($(this), e);
    });
}

function startConnector(connector, event) {
    // Start drawing a connector line
    event.stopPropagation();
    
    var nodeId = connector.closest('.workflow-node').data('node-id');
    var connectorType = connector.data('connector-type');
    
    // Store current connector data
    window.currentConnector = {
        nodeId: nodeId,
        type: connectorType,
        startX: connector.offset().left + connector.width() / 2,
        startY: connector.offset().top + connector.height() / 2
    };
    
    // Create temporary connection line
    $('<div>', {
        'id': 'temp-connector',
        'class': 'workflow-connector-line'
    }).appendTo('.workflow-canvas');
    
    // Add mouse move and up handlers
    $(document).on('mousemove.connector', moveConnector);
    $(document).on('mouseup.connector', endConnector);
}

function moveConnector(e) {
    // Update the temporary connector line as mouse moves
    if (window.currentConnector) {
        var line = $('#temp-connector');
        
        // Calculate line coordinates
        var x1 = window.currentConnector.startX;
        var y1 = window.currentConnector.startY;
        var x2 = e.pageX;
        var y2 = e.pageY;
        
        // Update line position
        updateLinePosition(line, x1, y1, x2, y2);
    }
}

function endConnector(e) {
    // End drawing connector line when mouse is released
    $(document).off('mousemove.connector');
    $(document).off('mouseup.connector');
    
    // Check if ended on a valid connector
    var target = $(e.target);
    if (target.hasClass('workflow-node-connector')) {
        var targetNodeId = target.closest('.workflow-node').data('node-id');
        var targetType = target.data('connector-type');
        
        // Only connect if types are compatible (output to input)
        if (window.currentConnector.type === 'output' && targetType === 'input') {
            createConnection(window.currentConnector.nodeId, targetNodeId);
        } else if (window.currentConnector.type === 'input' && targetType === 'output') {
            createConnection(targetNodeId, window.currentConnector.nodeId);
        }
    }
    
    // Remove temporary connector
    $('#temp-connector').remove();
    window.currentConnector = null;
}

function createConnection(sourceNodeId, targetNodeId) {
    // Create permanent connection between nodes
    var connectionId = 'connection-' + sourceNodeId + '-' + targetNodeId;
    
    // Check if connection already exists
    if ($('#' + connectionId).length > 0) {
        return;
    }
    
    $('<div>', {
        'id': connectionId,
        'class': 'workflow-connector-line workflow-connector-permanent',
        'data-source-node': sourceNodeId,
        'data-target-node': targetNodeId
    }).appendTo('.workflow-canvas');
    
    // Update connection line position
    updateConnection(connectionId);
}

function updateConnection(connectionId) {
    // Update position of a connection line
    var connection = $('#' + connectionId);
    var sourceNodeId = connection.data('source-node');
    var targetNodeId = connection.data('target-node');
    
    var sourceNode = $('#' + sourceNodeId);
    var targetNode = $('#' + targetNodeId);
    
    if (sourceNode.length === 0 || targetNode.length === 0) {
        connection.remove();
        return;
    }
    
    var sourceConnector = sourceNode.find('.workflow-node-connector.workflow-node-output');
    var targetConnector = targetNode.find('.workflow-node-connector.workflow-node-input');
    
    var x1 = sourceConnector.offset().left + sourceConnector.width() / 2;
    var y1 = sourceConnector.offset().top + sourceConnector.height() / 2;
    var x2 = targetConnector.offset().left + targetConnector.width() / 2;
    var y2 = targetConnector.offset().top + targetConnector.height() / 2;
    
    updateLinePosition(connection, x1, y1, x2, y2);
}

function updateConnections(nodeId) {
    // Update all connections related to a node
    $('.workflow-connector-permanent').each(function() {
        var connection = $(this);
        if (connection.data('source-node') === nodeId || connection.data('target-node') === nodeId) {
            updateConnection(connection.attr('id'));
        }
    });
}

function updateLinePosition(line, x1, y1, x2, y2) {
    // Update SVG line coordinates
    // Using transform for performance
    var length = Math.sqrt((x2 - x1) * (x2 - x1) + (y2 - y1) * (y2 - y1));
    var angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;
    var transform = 'rotate(' + angle + 'deg)';
    
    line.css({
        'position': 'absolute',
        'transform-origin': '0 0',
        'transform': transform,
        'width': length + 'px',
        'height': '2px',
        'left': x1 + 'px',
        'top': y1 + 'px'
    });
}

function selectNode(nodeId) {
    // Select a node and show its properties
    $('.workflow-node').removeClass('selected');
    $('#' + nodeId).addClass('selected');
    showProperties(nodeId);
}

function showProperties(nodeId) {
    // Show properties panel for selected node
    var node = $('#' + nodeId);
    var nodeType = node.data('node-type');
    
    $('#property-panel').show();
    $('#property-panel-title').text(nodeType + ' Properties');
    $('#property-node-id').val(nodeId);
    
    // Load node specific properties
    loadNodeProperties(nodeId);
}

function hideProperties() {
    // Hide properties panel
    $('#property-panel').hide();
    $('.workflow-node').removeClass('selected');
}

function loadNodeProperties(nodeId) {
    // Load properties for a specific node
    var node = $('#' + nodeId);
    var nodeType = node.data('node-type');
    var properties = node.data('properties') || {};
    
    // Clear existing properties
    $('#property-panel-fields').empty();
    
    // Add appropriate fields based on node type
    switch (nodeType) {
        case 'start':
            addPropertyField('name', 'Name', properties.name || 'Start');
            break;
        case 'end':
            addPropertyField('name', 'Name', properties.name || 'End');
            break;
        case 'task':
            addPropertyField('name', 'Name', properties.name || 'Task');
            addPropertyField('assignee', 'Assignee', properties.assignee || '');
            addPropertyField('dueDate', 'Due Date', properties.dueDate || '');
            break;
        case 'decision':
            addPropertyField('name', 'Name', properties.name || 'Decision');
            addPropertyField('condition', 'Condition', properties.condition || '');
            break;
        case 'email':
            addPropertyField('name', 'Name', properties.name || 'Email');
            addPropertyField('recipient', 'Recipient', properties.recipient || '');
            addPropertyField('template', 'Template', properties.template || '');
            break;
        case 'delay':
            addPropertyField('name', 'Name', properties.name || 'Delay');
            addPropertyField('duration', 'Duration (hours)', properties.duration || '24');
            break;
        default:
            addPropertyField('name', 'Name', properties.name || nodeType);
            break;
    }
}

function addPropertyField(id, label, value) {
    // Add a property field to the properties panel
    $('<div>', {
        'class': 'property-field'
    }).append(
        $('<label>', {
            'for': 'property-' + id
        }).text(label)
    ).append(
        $('<input>', {
            'type': 'text',
            'id': 'property-' + id,
            'class': 'form-control',
            'name': id,
            'value': value
        })
    ).appendTo('#property-panel-fields');
}

function applyProperties() {
    // Apply properties from panel to selected node
    var nodeId = $('#property-node-id').val();
    var node = $('#' + nodeId);
    
    if (node.length === 0) {
        return;
    }
    
    var properties = {};
    
    // Collect property values
    $('.property-field input').each(function() {
        var field = $(this);
        var name = field.attr('name');
        var value = field.val();
        properties[name] = value;
    });
    
    // Update node with new properties
    node.data('properties', properties);
    
    // Update node title if name property exists
    if (properties.name) {
        node.find('.workflow-node-title').text(properties.name);
    }
}

function saveWorkflow() {
    // Save workflow to server
    var workflowData = serializeWorkflow();
    
    $.ajax({
        url: $('#workflow-container').data('save-url'),
        type: 'POST',
        data: {
            workflow_id: $('#workflow-container').data('workflow-id'),
            name: $('#workflow-name').val(),
            description: $('#workflow-description').val(),
            workflow_data: JSON.stringify(workflowData),
            status: 1
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Workflow saved successfully');
                if (response.workflow_id) {
                    $('#workflow-container').data('workflow-id', response.workflow_id);
                }
            } else {
                alert('Error saving workflow: ' + response.error);
            }
        },
        error: function() {
            alert('Error saving workflow');
        }
    });
}

function loadWorkflow(workflowId) {
    // Load workflow data directly from the page if available
    var workflowData = $('#workflow-container').data('workflow-data');
    
    if (workflowData && typeof workflowData === 'string' && workflowData !== '{}') {
        try {
            var data = JSON.parse(workflowData);
            deserializeWorkflow(data);
        } catch (e) {
            console.error('Error parsing workflow data:', e);
        }
    }
}

function serializeWorkflow() {
    // Convert workflow to JSON data
    var nodes = [];
    var connections = [];
    
    // Serialize nodes
    $('.workflow-node').each(function() {
        var node = $(this);
        nodes.push({
            id: node.data('node-id'),
            type: node.data('node-type'),
            properties: node.data('properties') || {},
            position: {
                left: parseInt(node.css('left')),
                top: parseInt(node.css('top'))
            }
        });
    });
    
    // Serialize connections
    $('.workflow-connector-permanent').each(function() {
        var connection = $(this);
        connections.push({
            sourceId: connection.data('source-node'),
            targetId: connection.data('target-node')
        });
    });
    
    return {
        nodes: nodes,
        connections: connections,
        name: $('#workflow-name').val(),
        description: $('#workflow-description').val()
    };
}

function deserializeWorkflow(data) {
    // Load workflow from JSON data
    
    // Clear current workflow
    $('.workflow-canvas').empty();
    
    // Set workflow metadata
    $('#workflow-name').val(data.name || '');
    $('#workflow-description').val(data.description || '');
    
    // Create nodes
    if (data.nodes && data.nodes.length > 0) {
        data.nodes.forEach(function(nodeData) {
            createNodeFromData(nodeData);
        });
    }
    
    // Create connections
    if (data.connections && data.connections.length > 0) {
        data.connections.forEach(function(connectionData) {
            createConnection(connectionData.sourceId, connectionData.targetId);
        });
    }
}

function createNodeFromData(nodeData) {
    // Create a node from serialized data
    var newNode = $('<div>', {
        'class': 'workflow-node workflow-node-' + nodeData.type,
        'id': nodeData.id,
        'data-node-type': nodeData.type,
        'data-node-id': nodeData.id
    }).css({
        'left': nodeData.position.left,
        'top': nodeData.position.top
    }).append(
        $('<div>', {
            'class': 'workflow-node-title'
        }).text(nodeData.properties.name || nodeData.type.charAt(0).toUpperCase() + nodeData.type.slice(1))
    ).append(
        $('<div>', {
            'class': 'workflow-node-connectors'
        }).append(
            $('<div>', {
                'class': 'workflow-node-connector workflow-node-input',
                'data-connector-type': 'input'
            })
        ).append(
            $('<div>', {
                'class': 'workflow-node-connector workflow-node-output',
                'data-connector-type': 'output'
            })
        )
    );
    
    // Set properties data attribute
    newNode.data('properties', nodeData.properties);
    
    $('.workflow-canvas').append(newNode);
    
    // Make the new node draggable
    newNode.draggable({
        containment: '.workflow-canvas',
        stack: '.workflow-node',
        stop: function(event, ui) {
            // Update connections when node is moved
            updateConnections(nodeData.id);
        }
    });
    
    // Make node selectable
    newNode.on('click', function() {
        selectNode(nodeData.id);
    });
    
    // Initialize connectors for the new node
    newNode.find('.workflow-node-connector').on('mousedown', function(e) {
        startConnector($(this), e);
    });
}

function newWorkflow() {
    // Create a new workflow
    if (confirm('Are you sure you want to create a new workflow? Any unsaved changes will be lost.')) {
        $('.workflow-canvas').empty();
        $('#workflow-name').val('New Workflow');
        $('#workflow-description').val('');
        $('#workflow-container').data('workflow-id', '');
        hideProperties();
    }
}

function deleteWorkflow() {
    // Delete current workflow
    var workflowId = $('#workflow-container').data('workflow-id');
    
    if (!workflowId) {
        alert('No workflow to delete');
        return;
    }
    
    if (confirm('Are you sure you want to delete this workflow? This action cannot be undone.')) {
        window.location.href = $('#workflow-container').data('delete-url');
    }
}

function zoomIn() {
    // Zoom in to the workflow canvas
    var canvas = $('.workflow-canvas');
    var scale = parseFloat(canvas.data('scale') || 1);
    scale += 0.1;
    canvas.css('transform', 'scale(' + scale + ')');
    canvas.data('scale', scale);
}

function zoomOut() {
    // Zoom out from the workflow canvas
    var canvas = $('.workflow-canvas');
    var scale = parseFloat(canvas.data('scale') || 1);
    scale -= 0.1;
    if (scale < 0.1) scale = 0.1;
    canvas.css('transform', 'scale(' + scale + ')');
    canvas.data('scale', scale);
}

function fitWorkflow() {
    // Fit workflow to canvas
    var canvas = $('.workflow-canvas');
    canvas.css('transform', 'scale(1)');
    canvas.data('scale', 1);
} 