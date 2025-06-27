/**
 * Database Management JavaScript for Art2Cart Admin Panel
 */

// Database management functionality
const DatabaseManager = {
    // Initialize database management
    init() {
        this.bindEvents();
        this.loadDatabaseInfo();
        this.refreshBackupList();
    },

    // Bind event listeners
    bindEvents() {
        // File upload handling
        const restoreFile = document.getElementById('restoreFile');
        const uploadArea = document.getElementById('uploadArea');
        const fileInfo = document.getElementById('fileInfo');

        if (restoreFile) {
            restoreFile.addEventListener('change', (e) => {
                this.handleFileSelect(e.target.files[0]);
            });
        }

        if (uploadArea) {
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.handleFileSelect(files[0]);
                }
            });
        }
    },

    // Handle file selection
    handleFileSelect(file) {
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const restoreBtn = document.getElementById('restoreBtn');
        const uploadArea = document.getElementById('uploadArea');

        if (!file) return;

        // Validate file
        if (file.type !== 'application/sql' && !file.name.endsWith('.sql')) {
            this.showMessage('Only .sql files are allowed', 'error');
            return;
        }

        if (file.size > 100 * 1024 * 1024) { // 100MB limit
            this.showMessage('File too large. Maximum size is 100MB', 'error');
            return;
        }

        // Display file info
        fileName.textContent = file.name;
        fileSize.textContent = this.formatBytes(file.size);
        
        uploadArea.style.display = 'none';
        fileInfo.style.display = 'flex';
        restoreBtn.disabled = false;

        // Store file for later use
        this.selectedFile = file;
    },

    // Create backup
    async createBackup() {
        const btn = document.getElementById('createBackupBtn');
        const originalText = btn.innerHTML;
        
        try {
            // Update button state
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';

            const response = await fetch('admin/api/backup_api.php?action=create', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Backup created successfully! Download will start automatically.', 'success');
                
                // Trigger download
                const downloadLink = document.createElement('a');
                downloadLink.href = result.download_url;
                downloadLink.download = result.filename;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);

                // Refresh backup list
                this.refreshBackupList();
                
                // Update last backup date
                const lastBackupDate = document.getElementById('lastBackupDate');
                if (lastBackupDate) {
                    lastBackupDate.textContent = result.created;
                }
            } else {
                throw new Error(result.error || 'Backup creation failed');
            }
        } catch (error) {
            console.error('Backup error:', error);
            this.showMessage(`Backup failed: ${error.message}`, 'error');
        } finally {
            // Reset button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    },

    // Refresh backup list
    async refreshBackupList() {
        const backupList = document.getElementById('backupList');
        
        try {
            // Show loading
            backupList.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading backup history...</p>
                </div>
            `;

            const response = await fetch('admin/api/backup_api.php?action=list');
            const result = await response.json();

            if (result.success) {
                if (result.backups.length === 0) {
                    backupList.innerHTML = `
                        <div class="loading">
                            <i class="fas fa-info-circle"></i>
                            <p>No backups found</p>
                        </div>
                    `;
                } else {
                    backupList.innerHTML = result.backups.map(backup => `
                        <div class="backup-item">
                            <div class="backup-details">
                                <div class="backup-name">${backup.filename}</div>
                                <div class="backup-meta">
                                    ${backup.size_formatted} • Created on ${backup.created}
                                </div>
                            </div>
                            <div class="backup-actions-item">
                                <button class="btn btn-icon btn-primary" 
                                        onclick="DatabaseManager.downloadBackup('${backup.filename}')"
                                        title="Download">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn btn-icon btn-danger" 
                                        onclick="DatabaseManager.deleteBackup('${backup.filename}')"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `).join('');

                    // Update last backup date
                    if (result.backups.length > 0) {
                        const lastBackupDate = document.getElementById('lastBackupDate');
                        if (lastBackupDate) {
                            lastBackupDate.textContent = result.backups[0].created;
                        }
                    }
                }
            } else {
                throw new Error(result.error || 'Failed to load backups');
            }
        } catch (error) {
            console.error('Load backups error:', error);
            backupList.innerHTML = `
                <div class="loading">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading backups: ${error.message}</p>
                </div>
            `;
        }
    },

    // Download backup
    downloadBackup(filename) {
        const downloadUrl = `admin/backup_api.php?action=download&file=${encodeURIComponent(filename)}`;
        const downloadLink = document.createElement('a');
        downloadLink.href = downloadUrl;
        downloadLink.download = filename;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    },

    // Delete backup
    async deleteBackup(filename) {
        if (!confirm(`Are you sure you want to delete the backup "${filename}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('filename', filename);

            const response = await fetch('admin/api/backup_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Backup deleted successfully', 'success');
                this.refreshBackupList();
            } else {
                throw new Error(result.error || 'Delete failed');
            }
        } catch (error) {
            console.error('Delete backup error:', error);
            this.showMessage(`Delete failed: ${error.message}`, 'error');
        }
    },    // Confirm restore
    confirmRestore() {
        if (!this.selectedFile) {
            this.showMessage('Please select a backup file first', 'error');
            return;
        }

        this.showConfirmationModal(
            'Restore Database',
            `Are you sure you want to restore the database from "${this.selectedFile.name}"?
            
            <strong>Warning:</strong> This will replace all current data with the backup data. A safety backup will be created automatically before the restore operation.
            
            This action cannot be undone after completion.`,
            'Restore Database',
            () => DatabaseManager.performRestore()
        );    },

    // Perform restore
    async performRestore() {
        console.log('performRestore called');
        
        const restoreBtn = document.getElementById('restoreBtn');
        const originalText = restoreBtn ? restoreBtn.innerHTML : '';

        try {
            // Show progress modal
            this.showRestoreProgress();

            // Update button state
            if (restoreBtn) {
                restoreBtn.disabled = true;
                restoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';
            }

            // Create FormData
            const formData = new FormData();
            formData.append('backup_file', this.selectedFile);

            const response = await fetch('admin/api/restore_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            // Hide progress modal
            this.hideRestoreProgress();

            if (result.success) {
                // Show detailed success modal
                this.showRestoreSuccess({
                    filename: result.restored_from,
                    safetyBackup: result.safety_backup,
                    verification: result.verification,
                    preRestoreState: result.pre_restore_state,
                    timestamp: result.restore_timestamp
                });
                
                // Reset file selection
                this.resetFileSelection();
                
                // Refresh backup list to show safety backup
                this.refreshBackupList();
                
            } else {
                this.showRestoreError(result.error || 'Restore failed');
            }
        } catch (error) {
            console.error('Restore error:', error);
            this.hideRestoreProgress();
            this.showRestoreError(`Restore failed: ${error.message}`);
        } finally {
            // Reset button state
            if (restoreBtn) {
                restoreBtn.disabled = false;
                restoreBtn.innerHTML = originalText;
            }
        }
    },

    // Show restore progress modal
    showRestoreProgress() {
        const progressModal = document.createElement('div');
        progressModal.id = 'restoreProgressModal';
        progressModal.className = 'restore-modal-overlay';
        progressModal.innerHTML = `
            <div class="restore-modal-content">
                <div class="restore-modal-header">
                    <h3><i class="fas fa-database"></i> Restoring Database</h3>
                </div>
                <div class="restore-modal-body">
                    <div class="restore-progress-container">
                        <div class="restore-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <div class="restore-steps">
                            <div class="restore-step active">
                                <i class="fas fa-shield-alt"></i>
                                <span>Creating safety backup...</span>
                            </div>
                            <div class="restore-step active">
                                <i class="fas fa-upload"></i>
                                <span>Processing backup file...</span>
                            </div>
                            <div class="restore-step active">
                                <i class="fas fa-database"></i>
                                <span>Restoring database...</span>
                            </div>
                            <div class="restore-step">
                                <i class="fas fa-check-circle"></i>
                                <span>Verifying integrity...</span>
                            </div>
                        </div>
                    </div>
                    <div class="restore-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Please do not close this window or navigate away during the restore process.</p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(progressModal);
    },

    // Hide restore progress modal
    hideRestoreProgress() {
        const progressModal = document.getElementById('restoreProgressModal');
        if (progressModal) {
            progressModal.remove();
        }
    },

    // Show restore success modal
    showRestoreSuccess(restoreInfo) {
        const modal = document.createElement('div');
        modal.className = 'restore-modal-overlay';
        modal.innerHTML = `
            <div class="restore-modal-content restore-success">
                <div class="restore-modal-header success">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Database Restored Successfully!</h3>
                </div>
                <div class="restore-modal-body">
                    <div class="restore-details">
                        <div class="detail-group">
                            <h4><i class="fas fa-file-archive"></i> Restore Details</h4>
                            <div class="detail-item">
                                <label>Backup File:</label>
                                <span>${restoreInfo.filename}</span>
                            </div>
                            <div class="detail-item">
                                <label>Restore Time:</label>
                                <span>${restoreInfo.timestamp}</span>
                            </div>
                            <div class="detail-item">
                                <label>Safety Backup:</label>
                                <span>${restoreInfo.safetyBackup}</span>
                            </div>
                        </div>
                        
                        ${restoreInfo.verification ? `
                        <div class="detail-group">
                            <h4><i class="fas fa-chart-bar"></i> Database Verification</h4>
                            <div class="verification-stats">
                                <div class="stat-item">
                                    <div class="stat-value">${restoreInfo.verification.tables_count}</div>
                                    <div class="stat-label">Tables</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">${restoreInfo.verification.users_count}</div>
                                    <div class="stat-label">Users</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">${restoreInfo.verification.products_count}</div>
                                    <div class="stat-label">Products</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">${restoreInfo.verification.orders_count}</div>
                                    <div class="stat-label">Orders</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">${restoreInfo.verification.database_size}</div>
                                    <div class="stat-label">Size</div>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${restoreInfo.preRestoreState && restoreInfo.verification ? `
                        <div class="detail-group">
                            <h4><i class="fas fa-exchange-alt"></i> Before vs After</h4>
                            <div class="comparison-table">
                                <div class="comparison-header">
                                    <div>Metric</div>
                                    <div>Before</div>
                                    <div>After</div>
                                    <div>Change</div>
                                </div>
                                <div class="comparison-row">
                                    <div>Tables</div>
                                    <div>${restoreInfo.preRestoreState.tables_count}</div>
                                    <div>${restoreInfo.verification.tables_count}</div>
                                    <div class="${this.getChangeClass(restoreInfo.preRestoreState.tables_count, restoreInfo.verification.tables_count)}">
                                        ${this.getChangeText(restoreInfo.preRestoreState.tables_count, restoreInfo.verification.tables_count)}
                                    </div>
                                </div>
                                <div class="comparison-row">
                                    <div>Users</div>
                                    <div>${restoreInfo.preRestoreState.users_count}</div>
                                    <div>${restoreInfo.verification.users_count}</div>
                                    <div class="${this.getChangeClass(restoreInfo.preRestoreState.users_count, restoreInfo.verification.users_count)}">
                                        ${this.getChangeText(restoreInfo.preRestoreState.users_count, restoreInfo.verification.users_count)}
                                    </div>
                                </div>
                                <div class="comparison-row">
                                    <div>Products</div>
                                    <div>${restoreInfo.preRestoreState.products_count}</div>
                                    <div>${restoreInfo.verification.products_count}</div>
                                    <div class="${this.getChangeClass(restoreInfo.preRestoreState.products_count, restoreInfo.verification.products_count)}">
                                        ${this.getChangeText(restoreInfo.preRestoreState.products_count, restoreInfo.verification.products_count)}
                                    </div>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                      <div class="restore-actions">
                        <button class="btn btn-primary" id="verifyDataBtn">
                            <i class="fas fa-search"></i> Verify Data
                        </button>
                        <button class="btn btn-secondary" id="closeRestoreBtn">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button class="btn btn-info" id="refreshPageBtn">
                            <i class="fas fa-refresh"></i> Refresh Page
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Add event listeners
        const verifyBtn = modal.querySelector('#verifyDataBtn');
        const closeBtn = modal.querySelector('#closeRestoreBtn');
        const refreshBtn = modal.querySelector('#refreshPageBtn');

        verifyBtn.addEventListener('click', () => {
            modal.remove();
            DatabaseManager.verifyDatabaseIntegrity();
        });

        closeBtn.addEventListener('click', () => {
            modal.remove();
        });

        refreshBtn.addEventListener('click', () => {
            modal.remove();
            setTimeout(() => location.reload(), 1000);
        });

        // Auto refresh after 10 seconds
        setTimeout(() => {
            if (document.body.contains(modal)) {
                modal.remove();
                location.reload();
            }
        }, 10000);
    },

    // Show restore error modal
    showRestoreError(errorMessage) {
        const modal = document.createElement('div');
        modal.className = 'restore-modal-overlay';
        modal.innerHTML = `
            <div class="restore-modal-content restore-error">
                <div class="restore-modal-header error">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3>Restore Failed</h3>
                </div>
                <div class="restore-modal-body">
                    <div class="error-details">
                        <p><strong>Error Message:</strong></p>
                        <div class="error-message">${errorMessage}</div>
                        
                        <div class="error-suggestions">
                            <h4>Possible Solutions:</h4>
                            <ul>
                                <li>Ensure the backup file is valid and not corrupted</li>
                                <li>Check that you have sufficient database permissions</li>
                                <li>Verify the backup file format is compatible</li>
                                <li>Try creating a new backup and restoring from it</li>
                                <li>Contact administrator if the problem persists</li>
                            </ul>
                        </div>
                    </div>
                      <div class="restore-actions">
                        <button class="btn btn-primary" id="tryAgainBtn">
                            <i class="fas fa-redo"></i> Try Again
                        </button>
                        <button class="btn btn-secondary" id="closeErrorBtn">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Add event listeners
        const tryAgainBtn = modal.querySelector('#tryAgainBtn');
        const closeBtn = modal.querySelector('#closeErrorBtn');

        tryAgainBtn.addEventListener('click', () => {
            modal.remove();
            DatabaseManager.resetFileSelection();
        });

        closeBtn.addEventListener('click', () => {
            modal.remove();
        });
    },

    // Get change class for comparison
    getChangeClass(before, after) {
        if (after > before) return 'change-positive';
        if (after < before) return 'change-negative';
        return 'change-neutral';
    },

    // Get change text for comparison
    getChangeText(before, after) {
        const diff = after - before;
        if (diff > 0) return `+${diff}`;
        if (diff < 0) return `${diff}`;
        return 'No change';
    },

    // Verify database integrity (callable from console)
    async verifyDatabaseIntegrity() {
        try {
            const response = await fetch('admin/api/restore_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'verify_restore'
                })
            });

            const result = await response.json();

            if (result.success) {
                console.log('Database Verification Results:', result.verification);
                this.showMessage('Database verification completed. Check console for details.', 'success');
                return result.verification;
            } else {
                throw new Error(result.error || 'Verification failed');
            }
        } catch (error) {
            console.error('Database verification error:', error);
            this.showMessage(`Verification failed: ${error.message}`, 'error');
            return null;
        }
    },

    // Reset file selection
    resetFileSelection() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const restoreFile = document.getElementById('restoreFile');

        uploadArea.style.display = 'block';
        fileInfo.style.display = 'none';
        restoreFile.value = '';
        this.selectedFile = null;
    },

    // Load database info
    async loadDatabaseInfo() {
        try {
            const response = await fetch('admin/api/backup_api.php?action=info');
            const result = await response.json();

            if (result.success) {
                const info = result.info;
                
                const mysqlVersion = document.getElementById('mysqlVersion');
                const totalTables = document.getElementById('totalTables');
                const databaseSize = document.getElementById('databaseSize');

                if (mysqlVersion) mysqlVersion.textContent = info.mysql_version;
                if (totalTables) totalTables.textContent = info.table_count;
                if (databaseSize) databaseSize.textContent = info.database_size;
            }
        } catch (error) {
            console.error('Load database info error:', error);
        }
    },    // Show confirmation modal
    showConfirmationModal(title, message, confirmText, onConfirm) {
        const modal = document.createElement('div');
        modal.className = 'confirmation-modal';
        modal.innerHTML = `
            <div class="confirmation-content">
                <div class="confirmation-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="confirmation-title">${title}</div>
                <div class="confirmation-message">${message}</div>
                <div class="confirmation-actions">
                    <button class="btn btn-secondary" id="cancelRestoreBtn">
                        Cancel
                    </button>
                    <button class="btn btn-danger" id="confirmRestoreBtn">
                        ${confirmText}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Add event listeners
        const cancelBtn = modal.querySelector('#cancelRestoreBtn');
        const confirmBtn = modal.querySelector('#confirmRestoreBtn');

        cancelBtn.addEventListener('click', () => {
            modal.remove();
        });

        confirmBtn.addEventListener('click', () => {
            modal.remove();
            onConfirm();
        });

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    },

    // Show message
    showMessage(text, type = 'info') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());

        const message = document.createElement('div');
        message.className = `message ${type}`;
        
        const icon = type === 'success' ? 'check-circle' : 
                    type === 'error' ? 'exclamation-circle' : 
                    type === 'warning' ? 'exclamation-triangle' : 'info-circle';
        
        message.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${text}</span>
        `;

        // Insert at top of database tab
        const databaseTab = document.getElementById('database');
        if (databaseTab) {
            databaseTab.insertBefore(message, databaseTab.firstChild);
        }

        // Auto remove after 5 seconds
        setTimeout(() => {
            message.remove();
        }, 5000);
    },

    // Format bytes to human readable format
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
};

// Global functions for button onclick handlers
function createBackup() {
    DatabaseManager.createBackup();
}

function refreshBackupList() {
    DatabaseManager.refreshBackupList();
}

function confirmRestore() {
    DatabaseManager.confirmRestore();
}

// Additional global functions for restore operations
window.performRestore = function() {
    DatabaseManager.performRestore();
};

window.resetFileSelection = function() {
    DatabaseManager.resetFileSelection();
};

window.verifyDatabaseIntegrity = function() {
    return DatabaseManager.verifyDatabaseIntegrity();
};

// Initialize when database tab becomes active
document.addEventListener('DOMContentLoaded', function() {
    // Watch for tab changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const databaseTab = document.getElementById('database');
                if (databaseTab && databaseTab.classList.contains('active')) {
                    // Initialize database manager when tab becomes active
                    DatabaseManager.init();
                }
            }
        });
    });

    const databaseTab = document.getElementById('database');
    if (databaseTab) {
        observer.observe(databaseTab, { attributes: true });
        
        // Initialize immediately if tab is already active
        if (databaseTab.classList.contains('active')) {
            DatabaseManager.init();
        }
    }
});

// Global function for testing restore verification (callable from console)
window.testRestoreVerification = async function() {
    console.log('🔍 Testing database restore verification...');
    
    try {
        const verification = await DatabaseManager.verifyDatabaseIntegrity();
        if (verification) {
            console.log('✅ Database verification successful!');
            console.table(verification);
            return verification;
        }
    } catch (error) {
        console.error('❌ Database verification test failed:', error);
        return null;
    }
};

// Global function to test restore success modal
window.testRestoreSuccess = function() {
    console.log('🧪 Testing restore success modal...');
    DatabaseManager.showRestoreSuccess({
        filename: 'test_backup.sql',
        safetyBackup: 'safety_backup_test.sql',
        verification: {
            timestamp: new Date().toLocaleString(),
            tables_count: 15,
            users_count: 25,
            products_count: 150,
            orders_count: 75,
            categories_count: 8,
            ratings_count: 45,
            database_size: '12.5 MB'
        },
        preRestoreState: {
            tables_count: 14,
            users_count: 20,
            products_count: 140,
            orders_count: 70
        },
        timestamp: new Date().toLocaleString()
    });
};

// Global function to test restore error modal
window.testRestoreError = function() {
    console.log('🧪 Testing restore error modal...');
    DatabaseManager.showRestoreError('This is a test error message to demonstrate the error handling interface. The backup file could not be processed due to invalid SQL syntax.');
};

// Global function to test restore progress modal
window.testRestoreProgress = function() {
    console.log('🧪 Testing restore progress modal...');
    DatabaseManager.showRestoreProgress();
    
    // Hide after 5 seconds
    setTimeout(() => {
        DatabaseManager.hideRestoreProgress();
        console.log('✅ Progress modal test completed');
    }, 5000);
};

// Enhanced global functions for better testing
window.DatabaseTestSuite = {
    // Test all restore modals
    testAllModals: async function() {
        console.log('🧪 Running complete restore modal test suite...');
        
        // Test 1: Progress modal
        console.log('1. Testing progress modal...');
        DatabaseManager.showRestoreProgress();
        await new Promise(resolve => setTimeout(resolve, 2000));
        DatabaseManager.hideRestoreProgress();
        
        // Test 2: Success modal
        console.log('2. Testing success modal...');
        await new Promise(resolve => setTimeout(resolve, 1000));
        const verification = await DatabaseManager.verifyDatabaseIntegrity();
        if (verification) {
            DatabaseManager.showRestoreSuccess({
                filename: 'test_backup_suite.sql',
                safetyBackup: 'safety_backup_suite.sql',
                verification: verification,
                preRestoreState: {
                    tables_count: verification.tables_count - 2,
                    users_count: verification.users_count - 3,
                    products_count: verification.products_count - 7,
                },
                timestamp: new Date().toLocaleString()
            });
        }
        
        console.log('✅ Modal test suite completed!');
    },
    
    // Get current database stats
    getDatabaseStats: async function() {
        console.log('📊 Getting current database statistics...');
        const stats = await DatabaseManager.verifyDatabaseIntegrity();
        if (stats) {
            console.log('Database Statistics:');
            console.table(stats);
        }
        return stats;
    },
    
    // Test backup creation and listing
    testBackupOperations: async function() {
        console.log('💾 Testing backup operations...');
        
        try {
            // Test backup creation
            console.log('Creating test backup...');
            await DatabaseManager.createBackup();
            
            // Test backup listing
            console.log('Refreshing backup list...');
            await DatabaseManager.refreshBackupList();
            
            console.log('✅ Backup operations test completed!');
        } catch (error) {
            console.error('❌ Backup operations test failed:', error);
        }
    }
};

// Log available test functions
console.log(`
🧪 Art2Cart Database Restore Test Suite Available!

Available test functions:
• testRestoreVerification() - Test database verification
• testRestoreSuccess() - Test success modal
• testRestoreError() - Test error modal  
• testRestoreProgress() - Test progress modal
• testModalSequence() - Test complete sequence
• debugRestore() - Debug restore issues
• analyzeBackupFile() - Analyze selected backup file
• DatabaseTestSuite.testAllModals() - Test all modals
• DatabaseTestSuite.getDatabaseStats() - Get current stats

Usage: Just type any function name in the console and press Enter!

To debug file validation issues:
1. Select a backup file in the admin panel
2. Run analyzeBackupFile() in console
3. Check the output for SQL patterns
`);

// Debug function for restore issues
window.debugRestore = function() {
    console.log('🐛 Debugging restore functionality...');
    console.log('DatabaseManager exists:', typeof DatabaseManager !== 'undefined');
    console.log('performRestore exists:', typeof DatabaseManager?.performRestore === 'function');
    console.log('Selected file:', DatabaseManager?.selectedFile?.name || 'None');
    
    // Test progress modal
    if (DatabaseManager?.showRestoreProgress) {
        DatabaseManager.showRestoreProgress();
        setTimeout(() => DatabaseManager.hideRestoreProgress(), 3000);
    }
};

// Test complete modal sequence
window.testModalSequence = async function() {
    console.log('🎬 Testing modal sequence...');
    testRestoreProgress();
    setTimeout(() => testRestoreSuccess(), 4000);
};

// Simple backup file analyzer
window.analyzeBackupFile = function() {
    if (!DatabaseManager.selectedFile) {
        console.error('❌ No file selected. Please select a backup file first.');
        return;
    }
    
    const file = DatabaseManager.selectedFile;
    console.log('📁 Analyzing backup file:', file.name);
    console.log('📊 File size:', file.size, 'bytes');
    console.log('📋 File type:', file.type);
    
    // Read the file content
    const reader = new FileReader();
    reader.onload = function(e) {
        const content = e.target.result;
        console.log('📄 File content length:', content.length);
        console.log('📝 First 500 characters:');
        console.log(content.substring(0, 500));
        
        // Check for SQL patterns
        const sqlPatterns = [
            'CREATE TABLE',
            'INSERT INTO', 
            'DROP TABLE',
            'CREATE DATABASE',
            'USE ',
            'SET ',
            'LOCK TABLES',
            'UNLOCK TABLES',
            '-- MySQL dump',
            '-- phpMyAdmin',
            'mysqldump'
        ];
        
        console.log('� Checking SQL patterns:');
        sqlPatterns.forEach(pattern => {
            const found = content.toUpperCase().includes(pattern.toUpperCase());
            console.log(`  ${found ? '✅' : '❌'} ${pattern}: ${found ? 'FOUND' : 'NOT FOUND'}`);
        });
        
        // Check file extension
        const extension = file.name.split('.').pop().toLowerCase();
        console.log('📁 File extension:', extension, extension === 'sql' ? '✅' : '❌');
    };
    
    reader.readAsText(file);
};

// Quick file validation test
window.testFileValidation = function() {
    console.log('🧪 Testing file validation with different scenarios...');
    
    // Test 1: Create a valid SQL file
    const validSQL = `-- MySQL dump
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255)
);
INSERT INTO users VALUES (1, 'Test User');`;
    
    const validFile = new File([validSQL], 'test_valid.sql', { type: 'application/sql' });
    DatabaseManager.selectedFile = validFile;
    
    console.log('📁 Testing with valid SQL file...');
    debugBackupFile();
};
