<style>
.admin-dashboard {
    display: flex;
    min-height: 100vh;
    background-color: #f8fafc;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Sidebar */
.admin-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
}

.sidebar-header {
    padding: 24px;
    border-bottom: 1px solid #e2e8f0;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: #8b5cf6;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.logo-text {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
}

.sidebar-nav {
    flex: 1;
    padding: 16px 0;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.nav-item:hover {
    background-color: #f1f5f9;
    color: #334155;
}

.nav-item.active {
    background-color: #dbeafe;
    color: #1e40af;
    border-left-color: #3b82f6;
}

.nav-icon {
    width: 20px;
    height: 20px;
}

/* Main Content */
.admin-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-left: 280px;
}

.admin-header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 24px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left .page-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 4px 0;
}

.header-left .page-subtitle {
    font-size: 16px;
    color: #64748b;
    margin: 0;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 24px;
}

.time-range-dropdown {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 14px;
    color: #374151;
}

.notification-bell {
    position: relative;
    padding: 8px;
    cursor: pointer;
}

.notification-bell svg {
    width: 20px;
    height: 20px;
    color: #64748b;
}

.notification-count {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Notifications */
.notifications {
    position: relative;
    margin-right: 16px;
}

.notification-icon {
    position: relative;
    padding: 8px;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.2s;
}

.notification-icon:hover {
    color: #374151;
}

.notification-icon svg {
    width: 24px;
    height: 24px;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    min-width: 18px;
}

/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 360px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border: 1px solid #e5e7eb;
    z-index: 1000;
    margin-top: 8px;
}

.notification-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
}

.notification-count {
    font-size: 12px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 12px;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8fafc;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-icon-small {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-icon-small svg {
    width: 16px;
    height: 16px;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-item-title {
    font-size: 14px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 4px;
}

.notification-item-message {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
    margin-bottom: 4px;
}

.notification-item-time {
    font-size: 11px;
    color: #9ca3af;
}

/* Notification Types */
.notification-warning .notification-icon-small {
    background: #fef3c7;
    color: #f59e0b;
}

.notification-info .notification-icon-small {
    background: #dbeafe;
    color: #3b82f6;
}

.notification-success .notification-icon-small {
    background: #d1fae5;
    color: #059669;
}

.notification-error .notification-icon-small {
    background: #fee2e2;
    color: #ef4444;
}

.notification-empty {
    padding: 40px 20px;
    text-align: center;
    color: #6b7280;
}

.notification-empty svg {
    width: 32px;
    height: 32px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.notification-empty p {
    margin: 0;
    font-size: 14px;
}

.notification-footer {
    padding: 12px 20px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.notification-view-all {
    color: #3b82f6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.notification-view-all:hover {
    color: #2563eb;
    text-decoration: underline;
}

.user-profile {
    position: relative;
}

.user-profile-button {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.user-name {
    font-weight: 500;
    color: #374151;
}

.dropdown-arrow {
    width: 16px;
    height: 16px;
    color: #6b7280;
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    z-index: 50;
    margin-top: 8px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #374151;
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.2s;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.dropdown-item:hover {
    background-color: #f3f4f6;
}

.dropdown-item:first-child {
    border-radius: 8px 8px 0 0;
}

.dropdown-item:last-child {
    border-radius: 0 0 8px 8px;
}

.dropdown-icon {
    width: 16px;
    height: 16px;
    color: #6b7280;
}

/* Dashboard Content */
.dashboard-content {
    flex: 1;
    padding: 32px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.metrics-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.content-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.left-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.metric-content {
    flex: 1;
}

.metric-value {
    font-size: 28px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 4px;
}

.metric-label {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 8px;
}

.metric-change {
    font-size: 14px;
    font-weight: 500;
}

.metric-change.positive {
    color: #059669;
}

.metric-change.negative {
    color: #dc2626;
}

.metric-change.neutral {
    color: #6b7280;
}

.metric-icon {
    width: 40px;
    height: 40px;
    color: #3b82f6;
}

.metric-icon svg {
    width: 100%;
    height: 100%;
}

/* Popular Skills Card */
.popular-skills-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 20px 0;
}

.skills-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.skill-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.skill-icon {
    width: 32px;
    height: 32px;
    color: #059669;
}

.skill-icon svg {
    width: 100%;
    height: 100%;
}

.skill-info {
    flex: 1;
}

.skill-name {
    font-weight: 500;
    color: #1a202c;
    margin-bottom: 2px;
}

.skill-category {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 2px;
}

.skill-users {
    font-size: 12px;
    color: #64748b;
}

.skill-change {
    font-size: 12px;
    font-weight: 500;
}

.skill-change.positive {
    color: #059669;
}

.skill-change.negative {
    color: #dc2626;
}

.skill-change.neutral {
    color: #6b7280;
}

/* User Reports Card */
.user-reports-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-stats {
    display: flex;
    gap: 16px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 2px;
}

.stat-value.pending {
    color: #f59e0b;
}

.stat-value.resolved {
    color: #059669;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.reports-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 20px;
}

.report-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.report-item:hover {
    background: #f1f5f9;
}

.report-icon {
    width: 32px;
    height: 32px;
    color: #ef4444;
    flex-shrink: 0;
}

.report-icon svg {
    width: 100%;
    height: 100%;
}

.report-content {
    flex: 1;
    min-width: 0;
}

.report-title {
    font-weight: 500;
    color: #1a202c;
    margin-bottom: 4px;
    line-height: 1.4;
}

.report-meta {
    display: flex;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
    flex-wrap: wrap;
}

.report-reason {
    background: #e0e7ff;
    color: #3730a3;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: capitalize;
}

.report-status {
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: capitalize;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-under_review {
    background: #dbeafe;
    color: #1e40af;
}

.status-resolved {
    background: #d1fae5;
    color: #065f46;
}

.status-dismissed {
    background: #f3f4f6;
    color: #6b7280;
}

.report-time {
    color: #9ca3af;
}

.report-actions {
    flex-shrink: 0;
}

.view-report-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #3b82f6;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    transition: background-color 0.2s;
}

.view-report-btn:hover {
    background: #2563eb;
}

.view-report-btn svg {
    width: 16px;
    height: 16px;
}

.no-reports {
    text-align: center;
    color: #64748b;
    font-style: italic;
    padding: 20px;
}

.card-footer {
    border-top: 1px solid #e5e7eb;
    padding-top: 16px;
}

.view-all-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: color 0.2s;
}

.view-all-btn:hover {
    color: #2563eb;
}

.view-all-btn svg {
    width: 16px;
    height: 16px;
}

/* Recent Activity Card */
.recent-activity-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.activity-icon {
    width: 32px;
    height: 32px;
    color: #ef4444;
}

.activity-icon svg {
    width: 100%;
    height: 100%;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    color: #1a202c;
    margin-bottom: 4px;
}

.activity-meta {
    display: flex;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
}

.activity-role {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
}

.no-skills, .no-activity {
    text-align: center;
    color: #64748b;
    font-style: italic;
    padding: 20px;
}

/* Token Management Styles */
.token-management-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.token-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin: 16px 0;
}

.token-stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.token-stat-icon {
    width: 24px;
    height: 24px;
    color: #8b5cf6;
    flex-shrink: 0;
}

.token-stat-icon svg {
    width: 100%;
    height: 100%;
}

.token-stat-content {
    flex: 1;
    min-width: 0;
}

.token-stat-value {
    font-size: 16px;
    font-weight: 600;
    color: #1a202c;
    line-height: 1.2;
}

.token-stat-label {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.token-actions {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.action-group h4 {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #f1f5f9;
    color: #475569;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s;
}

.action-btn:hover {
    background: #e2e8f0;
    color: #334155;
}

.action-btn svg {
    width: 14px;
    height: 14px;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .metrics-row {
        grid-template-columns: 1fr;
    }

    .content-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        width: 280px;
    }

    .admin-sidebar.open {
        transform: translateX(0);
    }

    .admin-main {
        margin-left: 0;
        width: 100%;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        padding: 8px 0;
    }

    .nav-item {
        white-space: nowrap;
        border-left: 3px solid transparent;
        border-bottom: none;
    }

    .nav-item.active {
        border-left-color: #3b82f6;
        border-bottom: none;
    }

    .admin-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }

    .header-right {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
