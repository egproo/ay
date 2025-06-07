/**
 * Workflow Designer
 * JavaScript for handling workflow design interface
 */
class WorkflowDesigner {
    constructor(options) {
        this.options = Object.assign({
            container: '#workflow-designer',
            nodePalette: '#node-palette',
            canvas: '#workflow-canvas',
            propertyPanel: '#property-panel',
            saveButton: '#button-save',
            clearButton: '#button-clear',
            zoomInButton: '#button-zoom-in',
            zoomOutButton: '#button-zoom-out',
            scale: 1.0,
            nodes: [],
            connections: []
        }, options);

        this.container = document.querySelector(this.options.container);
        this.nodePalette = document.querySelector(this.options.nodePalette);
        this.canvas = document.querySelector(this.options.canvas);
        this.propertyPanel = document.querySelector(this.options.propertyPanel);
        this.saveButton = document.querySelector(this.options.saveButton);
        this.clearButton = document.querySelector(this.options.clearButton);
        this.zoomInButton = document.querySelector(this.options.zoomInButton);
        this.zoomOutButton = document.querySelector(this.options.zoomOutButton);
        
        this.draggedNode = null;
        this.selectedNode = null;
        this.selectedConnection = null;
        this.nodeCounter = 0;
        
        this.nodes = this.options.nodes;
        this.connections = this.options.connections;
        this.scale = this.options.scale;
        
        this.init();
    }
    
    init() {
        this.initNodePalette();
        this.initCanvas();
        this.initEvents();
        
        // Load existing workflow if available
        if (this.nodes.length) {
            this.renderWorkflow();
        }
    }
    
    initNodePalette() {
        // Define node types
        const nodeTypes = [
            { type: 'start', label: 'Start', icon: 'fa-play-circle', color: '#5cb85c' },
            { type: 'task', label: 'Task', icon: 'fa-tasks', color: '#5bc0de' },
            { type: 'decision', label: 'Decision', icon: 'fa-random', color: '#f0ad4e' },
            { type: 'email', label: 'Email', icon: 'fa-envelope', color: '#337ab7' },
            { type: 'delay', label: 'Delay', icon: 'fa-clock-o', color: '#777777' },
            { type: 'end', label: 'End', icon: 'fa-stop-circle', color: '#d9534f' }
        ];
        
        // Create node elements
        nodeTypes.forEach(nodeType => {
            const nodeElement = document.createElement('div');
            nodeElement.className = 'node-item';
            nodeElement.dataset.type = nodeType.type;
            nodeElement.innerHTML = `
                <div class="node-icon" style="background-color: ${nodeType.color}">
                    <i class="fa ${nodeType.icon}"></i>
                </div>
                <div class="node-label">${nodeType.label}</div>
            `;
            
            // Make node draggable
            nodeElement.draggable = true;
            nodeElement.addEventListener('dragstart', this.handleNodeDragStart.bind(this));
            
            this.nodePalette.appendChild(nodeElement);
        });
    }
    
    initCanvas() {
        // Make canvas a drop target
        this.canvas.addEventListener('dragover', this.handleCanvasDragOver.bind(this));
        this.canvas.addEventListener('drop', this.handleCanvasDrop.bind(this));
        this.canvas.addEventListener('click', this.handleCanvasClick.bind(this));
    }
    
    initEvents() {
        // Button events
        if (this.saveButton) {
            this.saveButton.addEventListener('click', this.handleSave.bind(this));
        }
        
        if (this.clearButton) {
            this.clearButton.addEventListener('click', this.handleClear.bind(this));
        }
        
        if (this.zoomInButton) {
            this.zoomInButton.addEventListener('click', this.handleZoomIn.bind(this));
        }
        
        if (this.zoomOutButton) {
            this.zoomOutButton.addEventListener('click', this.handleZoomOut.bind(this));
        }
    }
    
    handleNodeDragStart(event) {
        const nodeType = event.target.dataset.type;
        this.draggedNode = { type: nodeType };
        event.dataTransfer.setData('text/plain', nodeType);
        event.dataTransfer.effectAllowed = 'copy';
    }
    
    handleCanvasDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    }
    
    handleCanvasDrop(event) {
        event.preventDefault();
        
        if (!this.draggedNode) return;
        
        // Get node type from dragged node
        const nodeType = this.draggedNode.type;
        
        // Create new node
        const nodeId = `node-${this.nodeCounter++}`;
        const x = (event.offsetX - 50) / this.scale;
        const y = (event.offsetY - 25) / this.scale;
        
        // Define node properties based on type
        let nodeProperties = { id: nodeId, type: nodeType, x, y, label: nodeType.charAt(0).toUpperCase() + nodeType.slice(1) };
        
        switch (nodeType) {
            case 'start':
                nodeProperties.color = '#5cb85c';
                nodeProperties.icon = 'fa-play-circle';
                break;
            case 'task':
                nodeProperties.color = '#5bc0de';
                nodeProperties.icon = 'fa-tasks';
                break;
            case 'decision':
                nodeProperties.color = '#f0ad4e';
                nodeProperties.icon = 'fa-random';
                break;
            case 'email':
                nodeProperties.color = '#337ab7';
                nodeProperties.icon = 'fa-envelope';
                break;
            case 'delay':
                nodeProperties.color = '#777777';
                nodeProperties.icon = 'fa-clock-o';
                break;
            case 'end':
                nodeProperties.color = '#d9534f';
                nodeProperties.icon = 'fa-stop-circle';
                break;
        }
        
        // Add node to nodes array
        this.nodes.push(nodeProperties);
        
        // Render node
        this.renderNode(nodeProperties);
        
        // Reset dragged node
        this.draggedNode = null;
    }
    
    handleCanvasClick(event) {
        // Deselect current selection if clicking on canvas
        if (event.target === this.canvas) {
            this.deselectNode();
            this.deselectConnection();
        }
    }
    
    handleNodeClick(event, node) {
        event.stopPropagation();
        
        // Deselect current selection
        this.deselectConnection();
        
        // Select node
        this.selectNode(node);
        
        // Show node properties
        this.showNodeProperties(node);
    }
    
    handleConnectionClick(event, connection) {
        event.stopPropagation();
        
        // Deselect current selection
        this.deselectNode();
        
        // Select connection
        this.selectConnection(connection);
        
        // Show connection properties
        this.showConnectionProperties(connection);
    }
    
    handleConnectionStartDrag(event, node) {
        event.stopPropagation();
        // Implement connection dragging logic
    }
    
    handleConnectionEndDrag(event, node) {
        event.stopPropagation();
        // Implement connection dropping logic
    }
    
    handleSave() {
        // Get workflow data
        const workflowData = {
            nodes: this.nodes,
            connections: this.connections
        };
        
        // Convert to JSON string
        const workflowJson = JSON.stringify(workflowData);
        
        // Add to hidden input field
        const workflowInput = document.querySelector('input[name="workflow_data"]');
        if (workflowInput) {
            workflowInput.value = workflowJson;
        }
        
        return workflowJson;
    }
    
    handleClear() {
        // Clear all nodes and connections
        this.nodes = [];
        this.connections = [];
        
        // Clear canvas
        while (this.canvas.firstChild) {
            this.canvas.removeChild(this.canvas.firstChild);
        }
        
        // Clear property panel
        this.clearPropertyPanel();
    }
    
    handleZoomIn() {
        if (this.scale < 2.0) {
            this.scale += 0.1;
            this.updateCanvasScale();
        }
    }
    
    handleZoomOut() {
        if (this.scale > 0.5) {
            this.scale -= 0.1;
            this.updateCanvasScale();
        }
    }
    
    updateCanvasScale() {
        const canvasContent = this.canvas.querySelector('.canvas-content');
        if (canvasContent) {
            canvasContent.style.transform = `scale(${this.scale})`;
        } else {
            // Create canvas content container if it doesn't exist
            const contentContainer = document.createElement('div');
            contentContainer.className = 'canvas-content';
            contentContainer.style.transform = `scale(${this.scale})`;
            
            // Move all children to content container
            while (this.canvas.firstChild) {
                contentContainer.appendChild(this.canvas.firstChild);
            }
            
            this.canvas.appendChild(contentContainer);
        }
    }
    
    renderWorkflow() {
        // Clear canvas
        while (this.canvas.firstChild) {
            this.canvas.removeChild(this.canvas.firstChild);
        }
        
        // Render nodes
        this.nodes.forEach(node => {
            this.renderNode(node);
        });
        
        // Render connections
        this.connections.forEach(connection => {
            this.renderConnection(connection);
        });
    }
    
    renderNode(node) {
        const nodeElement = document.createElement('div');
        nodeElement.className = 'workflow-node';
        nodeElement.id = node.id;
        nodeElement.dataset.type = node.type;
        nodeElement.style.left = node.x + 'px';
        nodeElement.style.top = node.y + 'px';
        
        // Add node content
        nodeElement.innerHTML = `
            <div class="node-header" style="background-color: ${node.color}">
                <i class="fa ${node.icon}"></i>
                <span>${node.label}</span>
            </div>
            <div class="node-connectors">
                <div class="connector connector-in" data-node-id="${node.id}"></div>
                <div class="connector connector-out" data-node-id="${node.id}"></div>
            </div>
        `;
        
        // Make node draggable within canvas
        nodeElement.draggable = true;
        nodeElement.addEventListener('dragstart', (event) => {
            // Store the node being dragged
            this.draggingNode = node;
            
            // Set drag effect
            event.dataTransfer.effectAllowed = 'move';
            
            // Add dragging class
            nodeElement.classList.add('dragging');
            
            // Create transparent drag image
            const dragImage = document.createElement('div');
            dragImage.style.opacity = '0';
            document.body.appendChild(dragImage);
            event.dataTransfer.setDragImage(dragImage, 0, 0);
            
            // Set timeout to remove the drag image
            setTimeout(() => {
                document.body.removeChild(dragImage);
            }, 0);
        });
        
        nodeElement.addEventListener('drag', (event) => {
            // Update node position
            if (this.draggingNode) {
                const x = (event.clientX - this.canvas.getBoundingClientRect().left) / this.scale;
                const y = (event.clientY - this.canvas.getBoundingClientRect().top) / this.scale;
                
                // Update node position
                this.draggingNode.x = x - 50;
                this.draggingNode.y = y - 25;
                
                // Update node element position
                nodeElement.style.left = this.draggingNode.x + 'px';
                nodeElement.style.top = this.draggingNode.y + 'px';
                
                // Update all connections involving this node
                this.connections.forEach(connection => {
                    if (connection.sourceId === node.id || connection.targetId === node.id) {
                        this.updateConnectionPath(connection);
                    }
                });
            }
        });
        
        nodeElement.addEventListener('dragend', (event) => {
            // Reset dragging state
            this.draggingNode = null;
            
            // Remove dragging class
            nodeElement.classList.remove('dragging');
        });
        
        // Add event listeners
        nodeElement.addEventListener('click', (event) => this.handleNodeClick(event, node));
        
        // Add connector drag events
        const connectorOut = nodeElement.querySelector('.connector-out');
        if (connectorOut) {
            connectorOut.addEventListener('mousedown', (event) => this.startConnectionDrag(event, node, 'source'));
        }
        
        const connectorIn = nodeElement.querySelector('.connector-in');
        if (connectorIn) {
            connectorIn.addEventListener('mousedown', (event) => this.startConnectionDrag(event, node, 'target'));
        }
        
        // Add node element to canvas
        this.canvas.appendChild(nodeElement);
    }
    
    renderConnection(connection) {
        // Get source and target nodes
        const sourceNode = this.nodes.find(node => node.id === connection.sourceId);
        const targetNode = this.nodes.find(node => node.id === connection.targetId);
        
        if (!sourceNode || !targetNode) {
            return;
        }
        
        // Create SVG connection
        const svgNS = "http://www.w3.org/2000/svg";
        const svg = document.createElementNS(svgNS, "svg");
        svg.setAttribute("class", "workflow-connection");
        svg.setAttribute("id", `connection-${connection.id}`);
        svg.setAttribute("data-connection-id", connection.id);
        svg.style.position = "absolute";
        svg.style.top = "0";
        svg.style.left = "0";
        svg.style.width = "100%";
        svg.style.height = "100%";
        svg.style.pointerEvents = "none";
        
        // Create path
        const path = document.createElementNS(svgNS, "path");
        path.setAttribute("stroke", connection.color || "#90a4ae");
        path.setAttribute("stroke-width", "2");
        path.setAttribute("fill", "none");
        
        // Make connections clickable
        path.style.pointerEvents = "stroke";
        path.style.cursor = "pointer";
        
        // Add event listeners
        path.addEventListener("click", (event) => this.handleConnectionClick(event, connection));
        
        // Append path to SVG
        svg.appendChild(path);
        
        // Add to canvas before source and target nodes (to be below them)
        const firstNode = this.canvas.querySelector('.workflow-node');
        if (firstNode) {
            this.canvas.insertBefore(svg, firstNode);
        } else {
            this.canvas.appendChild(svg);
        }
        
        // Update path
        this.updateConnectionPath(connection);
    }
    
    updateConnectionPath(connection) {
        // Get source and target node elements
        const sourceNode = document.getElementById(connection.sourceId);
        const targetNode = document.getElementById(connection.targetId);
        
        if (!sourceNode || !targetNode) {
            return;
        }
        
        // Get source and target connectors
        const sourceConnector = sourceNode.querySelector('.connector-out');
        const targetConnector = targetNode.querySelector('.connector-in');
        
        if (!sourceConnector || !targetConnector) {
            return;
        }
        
        // Get connector positions
        const sourceRect = sourceConnector.getBoundingClientRect();
        const targetRect = targetConnector.getBoundingClientRect();
        const canvasRect = this.canvas.getBoundingClientRect();
        
        // Calculate connector center positions relative to canvas
        const sourceX = (sourceRect.left + sourceRect.width / 2 - canvasRect.left) / this.scale;
        const sourceY = (sourceRect.top + sourceRect.height / 2 - canvasRect.top) / this.scale;
        const targetX = (targetRect.left + targetRect.width / 2 - canvasRect.left) / this.scale;
        const targetY = (targetRect.top + targetRect.height / 2 - canvasRect.top) / this.scale;
        
        // Calculate control points for curve
        const dx = Math.abs(targetX - sourceX);
        const controlPoint = Math.min(100, dx / 2);
        
        // Create SVG path
        const pathData = `M ${sourceX} ${sourceY} C ${sourceX + controlPoint} ${sourceY}, ${targetX - controlPoint} ${targetY}, ${targetX} ${targetY}`;
        
        // Update path
        const svg = document.getElementById(`connection-${connection.id}`);
        if (svg) {
            const path = svg.querySelector('path');
            if (path) {
                path.setAttribute("d", pathData);
            }
        }
    }
    
    updateAllConnections() {
        this.connections.forEach(connection => {
            this.updateConnectionPath(connection);
        });
    }
    
    selectNode(node) {
        this.selectedNode = node;
        
        // Add selected class to node element
        const nodeElement = document.getElementById(node.id);
        if (nodeElement) {
            nodeElement.classList.add('selected');
        }
    }
    
    deselectNode() {
        if (this.selectedNode) {
            // Remove selected class from node element
            const nodeElement = document.getElementById(this.selectedNode.id);
            if (nodeElement) {
                nodeElement.classList.remove('selected');
            }
            
            this.selectedNode = null;
            
            // Clear property panel
            this.clearPropertyPanel();
        }
    }
    
    selectConnection(connection) {
        this.selectedConnection = connection;
        
        // Add selected class to connection element
        const connectionElement = document.getElementById(`connection-${connection.id}`);
        if (connectionElement) {
            connectionElement.classList.add('selected');
        }
    }
    
    deselectConnection() {
        if (this.selectedConnection) {
            // Remove selected class from connection element
            const connectionElement = document.getElementById(`connection-${this.selectedConnection.id}`);
            if (connectionElement) {
                connectionElement.classList.remove('selected');
            }
            
            this.selectedConnection = null;
            
            // Clear property panel
            this.clearPropertyPanel();
        }
    }
    
    showNodeProperties(node) {
        // Clear property panel
        this.clearPropertyPanel();
        
        // Create node properties form
        const propertiesForm = document.createElement('div');
        propertiesForm.className = 'properties-form';
        
        // Add node type specific properties
        propertiesForm.innerHTML = `
            <h4>Node Properties</h4>
            <div class="form-group">
                <label>Type</label>
                <input type="text" class="form-control" value="${node.type}" readonly>
            </div>
            <div class="form-group">
                <label>Label</label>
                <input type="text" class="form-control" value="${node.label}" id="node-label">
            </div>
        `;
        
        // Add type-specific properties
        switch (node.type) {
            case 'task':
                propertiesForm.innerHTML += `
                    <div class="form-group">
                        <label>Assign To</label>
                        <select class="form-control" id="node-assign">
                            <option value="user">User</option>
                            <option value="role">Role</option>
                            <option value="department">Department</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due In (Days)</label>
                        <input type="number" class="form-control" value="1" id="node-due-days">
                    </div>
                `;
                break;
            case 'decision':
                propertiesForm.innerHTML += `
                    <div class="form-group">
                        <label>Condition</label>
                        <textarea class="form-control" id="node-condition"></textarea>
                    </div>
                `;
                break;
            case 'email':
                propertiesForm.innerHTML += `
                    <div class="form-group">
                        <label>Template</label>
                        <select class="form-control" id="node-template">
                            <option value="notification">Notification</option>
                            <option value="approval">Approval</option>
                            <option value="rejection">Rejection</option>
                        </select>
                    </div>
                `;
                break;
            case 'delay':
                propertiesForm.innerHTML += `
                    <div class="form-group">
                        <label>Delay (Hours)</label>
                        <input type="number" class="form-control" value="24" id="node-delay">
                    </div>
                `;
                break;
        }
        
        // Add apply button
        propertiesForm.innerHTML += `
            <button type="button" class="btn btn-primary" id="apply-properties">Apply</button>
            <button type="button" class="btn btn-danger" id="delete-node">Delete</button>
        `;
        
        // Add properties form to property panel
        this.propertyPanel.appendChild(propertiesForm);
        
        // Add event listeners
        const applyButton = document.getElementById('apply-properties');
        if (applyButton) {
            applyButton.addEventListener('click', () => this.applyNodeProperties(node));
        }
        
        const deleteButton = document.getElementById('delete-node');
        if (deleteButton) {
            deleteButton.addEventListener('click', () => this.deleteNode(node));
        }
    }
    
    showConnectionProperties(connection) {
        // Clear property panel
        this.clearPropertyPanel();
        
        // Create connection properties form
        const propertiesForm = document.createElement('div');
        propertiesForm.className = 'properties-form';
        
        // Add connection properties
        propertiesForm.innerHTML = `
            <h4>Connection Properties</h4>
            <div class="form-group">
                <label>Label</label>
                <input type="text" class="form-control" value="${connection.label || ''}" id="connection-label">
            </div>
            <div class="form-group">
                <label>Condition</label>
                <textarea class="form-control" id="connection-condition">${connection.condition || ''}</textarea>
            </div>
            <div class="form-group">
                <label>Color</label>
                <select class="form-control" id="connection-color">
                    <option value="#90a4ae" ${!connection.color || connection.color === '#90a4ae' ? 'selected' : ''}>Default</option>
                    <option value="#4caf50" ${connection.color === '#4caf50' ? 'selected' : ''}>Success</option>
                    <option value="#f44336" ${connection.color === '#f44336' ? 'selected' : ''}>Error</option>
                    <option value="#ff9800" ${connection.color === '#ff9800' ? 'selected' : ''}>Warning</option>
                    <option value="#2196f3" ${connection.color === '#2196f3' ? 'selected' : ''}>Info</option>
                </select>
            </div>
        `;
        
        // Add apply and delete buttons
        propertiesForm.innerHTML += `
            <button type="button" class="btn btn-primary" id="apply-connection-properties">Apply</button>
            <button type="button" class="btn btn-danger" id="delete-connection">Delete</button>
        `;
        
        // Add properties form to property panel
        this.propertyPanel.appendChild(propertiesForm);
        
        // Add event listeners
        const applyButton = document.getElementById('apply-connection-properties');
        if (applyButton) {
            applyButton.addEventListener('click', () => this.applyConnectionProperties(connection));
        }
        
        const deleteButton = document.getElementById('delete-connection');
        if (deleteButton) {
            deleteButton.addEventListener('click', () => this.deleteConnection(connection));
        }
    }
    
    applyNodeProperties(node) {
        // Get values from property form
        const labelInput = document.getElementById('node-label');
        if (labelInput) {
            node.label = labelInput.value;
        }
        
        // Update type-specific properties
        switch (node.type) {
            case 'task':
                const assignSelect = document.getElementById('node-assign');
                const dueDaysInput = document.getElementById('node-due-days');
                
                if (assignSelect) {
                    node.assign = assignSelect.value;
                }
                
                if (dueDaysInput) {
                    node.dueDays = dueDaysInput.value;
                }
                break;
            case 'decision':
                const conditionTextarea = document.getElementById('node-condition');
                
                if (conditionTextarea) {
                    node.condition = conditionTextarea.value;
                }
                break;
            case 'email':
                const templateSelect = document.getElementById('node-template');
                
                if (templateSelect) {
                    node.template = templateSelect.value;
                }
                break;
            case 'delay':
                const delayInput = document.getElementById('node-delay');
                
                if (delayInput) {
                    node.delay = delayInput.value;
                }
                break;
        }
        
        // Update node element
        const nodeElement = document.getElementById(node.id);
        if (nodeElement) {
            const nodeLabel = nodeElement.querySelector('.node-header span');
            if (nodeLabel) {
                nodeLabel.textContent = node.label;
            }
        }
    }
    
    deleteNode(node) {
        // Remove node from nodes array
        const nodeIndex = this.nodes.findIndex(n => n.id === node.id);
        if (nodeIndex !== -1) {
            this.nodes.splice(nodeIndex, 1);
        }
        
        // Remove connections related to this node
        this.connections = this.connections.filter(conn => 
            conn.sourceId !== node.id && conn.targetId !== node.id
        );
        
        // Remove node element from canvas
        const nodeElement = document.getElementById(node.id);
        if (nodeElement) {
            nodeElement.remove();
        }
        
        // Clear property panel
        this.clearPropertyPanel();
    }
    
    clearPropertyPanel() {
        // Clear property panel
        while (this.propertyPanel.firstChild) {
            this.propertyPanel.removeChild(this.propertyPanel.firstChild);
        }
    }
    
    startConnectionDrag(event, node, role) {
        event.stopPropagation();
        
        // Only allow starting connections from output connector
        if (role !== 'source') {
            return;
        }
        
        // Create temporary connection
        this.tempConnection = {
            id: 'temp',
            sourceId: node.id,
            targetId: null,
            sourceX: event.clientX,
            sourceY: event.clientY
        };
        
        // Create temporary connection element
        const svgNS = "http://www.w3.org/2000/svg";
        const svg = document.createElementNS(svgNS, "svg");
        svg.setAttribute("class", "workflow-connection temp-connection");
        svg.setAttribute("id", "temp-connection");
        svg.style.position = "absolute";
        svg.style.top = "0";
        svg.style.left = "0";
        svg.style.width = "100%";
        svg.style.height = "100%";
        svg.style.pointerEvents = "none";
        
        // Create path
        const path = document.createElementNS(svgNS, "path");
        path.setAttribute("stroke", "#90a4ae");
        path.setAttribute("stroke-width", "2");
        path.setAttribute("stroke-dasharray", "5,5");
        path.setAttribute("fill", "none");
        
        // Append path to SVG
        svg.appendChild(path);
        
        // Add to canvas
        this.canvas.appendChild(svg);
        
        // Set up mouse move and up handlers
        document.addEventListener('mousemove', this.connectionDragMove = this.handleConnectionDragMove.bind(this));
        document.addEventListener('mouseup', this.connectionDragEnd = this.handleConnectionDragEnd.bind(this));
    }
    
    handleConnectionDragMove(event) {
        if (!this.tempConnection) {
            return;
        }
        
        // Update temporary connection
        const sourceNode = document.getElementById(this.tempConnection.sourceId);
        if (!sourceNode) {
            return;
        }
        
        // Get source connector
        const sourceConnector = sourceNode.querySelector('.connector-out');
        if (!sourceConnector) {
            return;
        }
        
        // Get canvas rect
        const canvasRect = this.canvas.getBoundingClientRect();
        
        // Get connector position
        const sourceRect = sourceConnector.getBoundingClientRect();
        const sourceX = (sourceRect.left + sourceRect.width / 2 - canvasRect.left) / this.scale;
        const sourceY = (sourceRect.top + sourceRect.height / 2 - canvasRect.top) / this.scale;
        
        // Get mouse position
        const targetX = (event.clientX - canvasRect.left) / this.scale;
        const targetY = (event.clientY - canvasRect.top) / this.scale;
        
        // Calculate control points for curve
        const dx = Math.abs(targetX - sourceX);
        const controlPoint = Math.min(100, dx / 2);
        
        // Create SVG path
        const pathData = `M ${sourceX} ${sourceY} C ${sourceX + controlPoint} ${sourceY}, ${targetX - controlPoint} ${targetY}, ${targetX} ${targetY}`;
        
        // Update path
        const svg = document.getElementById('temp-connection');
        if (svg) {
            const path = svg.querySelector('path');
            if (path) {
                path.setAttribute("d", pathData);
            }
        }
        
        // Highlight connector if over a valid target
        this.highlightTargetConnector(event);
    }
    
    highlightTargetConnector(event) {
        // Remove previous highlight
        const previousHighlight = document.querySelector('.connector-highlight');
        if (previousHighlight) {
            previousHighlight.classList.remove('connector-highlight');
        }
        
        // Check if over a connector
        const elementUnderMouse = document.elementFromPoint(event.clientX, event.clientY);
        
        if (elementUnderMouse && elementUnderMouse.classList.contains('connector-in')) {
            // Get target node
            const targetNodeId = elementUnderMouse.dataset.nodeId;
            
            // Don't allow connecting to self
            if (targetNodeId === this.tempConnection.sourceId) {
                return;
            }
            
            // Highlight connector
            elementUnderMouse.classList.add('connector-highlight');
            
            // Store target node id
            this.tempConnection.targetId = targetNodeId;
        } else {
            // Reset target
            this.tempConnection.targetId = null;
        }
    }
    
    handleConnectionDragEnd(event) {
        // Remove move and up handlers
        document.removeEventListener('mousemove', this.connectionDragMove);
        document.removeEventListener('mouseup', this.connectionDragEnd);
        
        // Remove temporary connection
        const tempConnection = document.getElementById('temp-connection');
        if (tempConnection) {
            tempConnection.remove();
        }
        
        // Remove highlight
        const highlight = document.querySelector('.connector-highlight');
        if (highlight) {
            highlight.classList.remove('connector-highlight');
        }
        
        // Create permanent connection if target is valid
        if (this.tempConnection && this.tempConnection.targetId) {
            // Create connection object
            const connection = {
                id: `connection-${Date.now()}`,
                sourceId: this.tempConnection.sourceId,
                targetId: this.tempConnection.targetId,
                label: '',
                condition: '',
                color: '#90a4ae'
            };
            
            // Add to connections array
            this.connections.push(connection);
            
            // Render connection
            this.renderConnection(connection);
        }
        
        // Reset temp connection
        this.tempConnection = null;
    }
    
    applyConnectionProperties(connection) {
        // Get values from property form
        const labelInput = document.getElementById('connection-label');
        if (labelInput) {
            connection.label = labelInput.value;
        }
        
        const conditionTextarea = document.getElementById('connection-condition');
        if (conditionTextarea) {
            connection.condition = conditionTextarea.value;
        }
        
        const colorSelect = document.getElementById('connection-color');
        if (colorSelect) {
            connection.color = colorSelect.value;
        }
        
        // Update connection element
        const connectionElement = document.getElementById(`connection-${connection.id}`);
        if (connectionElement) {
            const path = connectionElement.querySelector('path');
            if (path) {
                path.setAttribute('stroke', connection.color || '#90a4ae');
            }
        }
    }
    
    deleteConnection(connection) {
        // Remove connection from connections array
        const connectionIndex = this.connections.findIndex(c => c.id === connection.id);
        if (connectionIndex !== -1) {
            this.connections.splice(connectionIndex, 1);
        }
        
        // Remove connection element from canvas
        const connectionElement = document.getElementById(`connection-${connection.id}`);
        if (connectionElement) {
            connectionElement.remove();
        }
        
        // Clear property panel
        this.clearPropertyPanel();
    }
}

// Initialize the workflow designer when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if workflow designer container exists
    const workflowDesignerContainer = document.getElementById('workflow-designer');
    
    if (workflowDesignerContainer) {
        // Initialize workflow designer
        window.workflowDesigner = new WorkflowDesigner();
        
        // Load existing workflow data if available
        const workflowInput = document.querySelector('input[name="workflow_data"]');
        if (workflowInput && workflowInput.value) {
            try {
                const workflowData = JSON.parse(workflowInput.value);
                
                if (workflowData.nodes) {
                    window.workflowDesigner.nodes = workflowData.nodes;
                }
                
                if (workflowData.connections) {
                    window.workflowDesigner.connections = workflowData.connections;
                }
                
                window.workflowDesigner.renderWorkflow();
            } catch (error) {
                console.error('Error loading workflow data:', error);
            }
        }
    }
}); 