// Staff Dashboard Enhanced Functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize dashboard components
    initializeDashboard();
    
    // Add smooth animations and interactions
    addDashboardInteractions();
    
    // Start auto-refresh timer
    startAutoRefresh();
});

function initializeDashboard() {
    console.log('Initializing Staff Dashboard...');
    
    // Add loading states to stats cards
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

function addDashboardInteractions() {
    // Enhanced hover effects for quick action buttons
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    quickActionBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
            this.style.boxShadow = '0 10px 30px rgba(34, 197, 94, 0.3)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Enhanced hover effects for stats cards
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = '0 12px 30px rgba(0, 0, 0, 0.3)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Add click effects to announcement items
    const announcementItems = document.querySelectorAll('.announcement-item');
    announcementItems.forEach(item => {
        item.addEventListener('click', function() {
            // Add a subtle click effect
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
}

function startAutoRefresh() {
    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        console.log('Dashboard auto-refresh triggered');
        // You can implement AJAX calls here to refresh specific data
        // For now, we'll just log the event
    }, 300000); // 5 minutes
}

// Function to refresh announcements
function refreshAnnouncements() {
    const refreshBtn = document.querySelector('[onclick="refreshAnnouncements()"]');
    const icon = refreshBtn.querySelector('i');
    
    // Add spinning animation
    icon.style.animation = 'spin 1s linear infinite';
    
    // Simulate refresh delay
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Function to mark announcement as read
function markAsRead(announcementId) {
    console.log('Marking announcement ' + announcementId + ' as read');
    
    // You can implement AJAX call here to mark announcement as read
    // For now, we'll show a success message
    
    // Find the announcement item
    const announcementItem = document.querySelector(`[data-announcement-id="${announcementId}"]`);
    if (announcementItem) {
        // Add a subtle success effect
        announcementItem.style.borderColor = '#22c55e';
        announcementItem.style.backgroundColor = 'rgba(34, 197, 94, 0.1)';
        
        // Show success message
        showNotification('Announcement marked as read!', 'success');
        
        // Reset styles after a delay
        setTimeout(() => {
            announcementItem.style.borderColor = '';
            announcementItem.style.backgroundColor = '';
        }, 2000);
    }
}

// Function to show notifications
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        background: rgba(30, 41, 59, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    `;
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Function to update dashboard stats (placeholder for future AJAX implementation)
function updateDashboardStats() {
    // This function can be used to update stats via AJAX
    // For now, it's a placeholder
    console.log('Updating dashboard stats...');
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .stats-card {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s ease;
    }
    
    .quick-action-btn {
        transition: all 0.3s ease;
    }
    
    .announcement-item {
        transition: all 0.3s ease;
    }
`;

document.head.appendChild(style);

// Export functions for global access
window.dashboardFunctions = {
    refreshAnnouncements,
    markAsRead,
    showNotification,
    updateDashboardStats
};
