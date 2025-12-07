# Asset Management Mobile API Documentation

## 📋 Overview

This document provides comprehensive API documentation for the **Asset Management Mobile Application** integration with the Open Core HR SaaS platform. The mobile app enables employees to manage their asset assignments through a streamlined approval workflow system.

---

## 🚀 Getting Started

### Base Configuration
- **Base URL**: `https://{tenant-domain}/api`
- **Authentication**: Bearer Token (JWT via Laravel Sanctum/Passport)
- **Content Type**: `application/json`
- **API Version**: V1

### Prerequisites
- Valid tenant domain
- Employee credentials
- Mobile device with internet connectivity

---

## 🔐 Authentication

**Note**: All asset management API endpoints require authentication. Users must first authenticate using the existing authentication system to obtain a Bearer token, which must be included in all subsequent requests.

**Authentication Required**: All endpoints below require the following header:
```json
{
  "Authorization": "Bearer {token}",
  "Accept": "application/json"
}
```

**Token Management**:
- Obtain tokens through the existing authentication system
- Store tokens securely in device keychain/secure storage
- Handle token expiry with refresh mechanisms
- Revoke tokens on logout through existing auth endpoints

---

## 📊 Dashboard APIs

### 1. Employee Dashboard
**Purpose**: Provides overview of employee's asset management status with key metrics and recent activity

**Endpoint**: `GET /api/employee/assets/dashboard`

**Request Headers**:
```json
{
  "Authorization": "Bearer {token}",
  "Accept": "application/json"
}
```

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_assets": 5,
      "pending_assignments": 2,
      "active_assignments": 3,
      "overdue_returns": 0,
      "return_requests_pending": 1
    },
    "recent_assignments": [
      {
        "id": 15,
        "asset": {
          "name": "MacBook Pro 16\"",
          "asset_tag": "LT001",
          "category": "Laptops",
          "image": "https://example.com/laptop.jpg"
        },
        "status": "pending_approval",
        "assigned_at": "2025-10-10T10:30:00Z",
        "expected_return_date": "2025-12-31T00:00:00Z",
        "days_pending": 1
      }
    ],
    "notifications": {
      "unread_count": 3,
      "recent": [
        {
          "id": "notif_001",
          "title": "New Asset Assignment",
          "message": "MacBook Pro assigned for approval",
          "created_at": "2025-10-10T10:30:00Z",
          "type": "assignment_pending"
        }
      ]
    },
    "quick_stats": {
      "assets_this_month": 2,
      "average_response_time_hours": 4.5,
      "total_assignments_this_year": 8
    }
  }
}
```

**Use Cases**:
- App home screen display
- Quick overview of pending actions
- Recent activity feed
- Push notification badge counts

---

## 📦 Asset Assignment APIs

### 1. Get Pending Assignments
**Purpose**: Retrieve list of asset assignments waiting for employee approval

**Endpoint**: `GET /api/employee/assets/pending-assignments`

**Query Parameters**:
- `page` (optional): Page number for pagination (default: 1)
- `per_page` (optional): Items per page (default: 10, max: 50)
- `category_id` (optional): Filter by asset category
- `sort` (optional): Sort order (newest, oldest, priority)

**Request Headers**:
```json
{
  "Authorization": "Bearer {token}",
  "Accept": "application/json"
}
```

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 25,
        "asset": {
          "id": 101,
          "name": "iPhone 15 Pro",
          "asset_tag": "PH001",
          "model": "iPhone 15 Pro 256GB",
          "serial_number": "ABC123456789",
          "category": {
            "id": 2,
            "name": "Mobile Devices",
            "icon": "phone"
          },
          "condition": "new",
          "purchase_date": "2025-09-15",
          "warranty_expiry": "2027-09-15",
          "images": [
            "https://example.com/iphone-front.jpg",
            "https://example.com/iphone-back.jpg"
          ]
        },
        "assignment_details": {
          "assigned_at": "2025-10-10T09:00:00Z",
          "expected_return_date": "2026-10-10T00:00:00Z",
          "condition_out": "new",
          "notes": "Company iPhone for work use. Please handle with care.",
          "priority": "normal"
        },
        "assigned_by": {
          "name": "IT Administrator",
          "email": "admin@company.com",
          "department": "IT Department"
        },
        "metadata": {
          "days_pending": 1,
          "reminder_sent": false,
          "can_reject": true,
          "rejection_reason_required": false
        }
      }
    ],
    "pagination": {
      "total": 3,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

**Use Cases**:
- Pending assignments screen
- Assignment approval workflow
- Asset details display
- Notification handling

---

### 2. Get My Assets
**Purpose**: Retrieve list of assets currently assigned to the employee

**Endpoint**: `GET /api/employee/assets/my-assets`

**Query Parameters**:
- `status` (optional): Filter by status (active, return_requested, all)
- `category_id` (optional): Filter by asset category
- `search` (optional): Search by asset name or tag
- `page` (optional): Page number
- `per_page` (optional): Items per page

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 18,
        "asset": {
          "id": 205,
          "name": "Dell Monitor 27\"",
          "asset_tag": "MON005",
          "model": "Dell UltraSharp U2723QE",
          "category": {
            "id": 3,
            "name": "Monitors",
            "icon": "monitor"
          },
          "condition": "good",
          "images": ["https://example.com/monitor.jpg"]
        },
        "assignment_status": "active",
        "assignment_details": {
          "approved_at": "2025-09-20T14:30:00Z",
          "expected_return_date": "2026-09-20T00:00:00Z",
          "condition_out": "good",
          "notes": "Monitor for dual-screen setup"
        },
        "usage_metrics": {
          "days_in_possession": 20,
          "can_request_return": true,
          "return_window_expires": null
        },
        "return_status": {
          "return_requested": false,
          "return_request_date": null,
          "return_approved": null
        }
      }
    ],
    "pagination": {
      "total": 3,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

**Use Cases**:
- My Assets screen
- Asset inventory management
- Return request initiation
- Asset usage tracking

---

### 3. Get Assignment History
**Purpose**: Retrieve complete history of employee's asset assignments including past assignments, rejections, and returns

**Endpoint**: `GET /api/employee/assets/history`

**Query Parameters**:
- `status` (optional): Filter by final status (approved, rejected, returned, all)
- `date_from` (optional): Filter from date (YYYY-MM-DD)
- `date_to` (optional): Filter to date (YYYY-MM-DD)
- `category_id` (optional): Filter by asset category
- `page` (optional): Page number
- `per_page` (optional): Items per page

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 12,
        "asset": {
          "name": "MacBook Air M2",
          "asset_tag": "LT003",
          "category": "Laptops"
        },
        "timeline": {
          "assigned_at": "2025-08-01T09:00:00Z",
          "employee_response": "approved",
          "employee_response_at": "2025-08-01T11:30:00Z",
          "return_requested_at": "2025-09-15T16:00:00Z",
          "return_approved_at": "2025-09-16T10:00:00Z",
          "returned_at": "2025-09-18T14:30:00Z"
        },
        "final_status": "returned",
        "assignment_duration": {
          "total_days": 48,
          "active_days": 45
        },
        "conditions": {
          "condition_out": "new",
          "condition_in": "good"
        },
        "notes": {
          "assignment_notes": "Laptop for project work",
          "return_reason": "Project completed successfully"
        }
      }
    ],
    "pagination": {
      "total": 12,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    },
    "summary": {
      "total_assignments": 12,
      "approved_assignments": 10,
      "rejected_assignments": 2,
      "returned_assets": 8,
      "average_assignment_duration_days": 45
    }
  }
}
```

**Use Cases**:
- Assignment history screen
- Performance analytics
- Compliance reporting
- Asset lifecycle tracking

---

## ✅ Employee Action APIs

### 1. Respond to Assignment (Approve/Reject)
**Purpose**: Allow employee to approve or reject an asset assignment

**Endpoint**: `POST /api/employee/assets/assignments/{assignment_id}/respond`

**Path Parameters**:
- `assignment_id`: ID of the assignment to respond to

**Request Body for Approval**:
```json
{
  "response": "approve",
  "notes": "Thank you for assigning this laptop. I will take good care of it and use it responsibly for my work."
}
```

**Request Body for Rejection**:
```json
{
  "response": "reject",
  "notes": "I already have a similar device assigned. I don't need another laptop at this time. Thank you for considering me."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Assignment approved successfully. The asset is now assigned to you.",
  "data": {
    "assignment_id": 25,
    "status": "approved",
    "responded_at": "2025-10-11T10:30:00Z",
    "asset": {
      "name": "iPhone 15 Pro",
      "asset_tag": "PH001"
    },
    "next_actions": [
      {
        "action": "coordinate_pickup",
        "description": "Please coordinate with IT department for asset pickup",
        "contact": "it-support@company.com"
      }
    ]
  }
}
```

**Error Responses**:

**Assignment Not Found (404)**:
```json
{
  "success": false,
  "message": "Assignment not found or not accessible",
  "error_code": "ASSIGNMENT_NOT_FOUND"
}
```

**Already Responded (422)**:
```json
{
  "success": false,
  "message": "You have already responded to this assignment",
  "error_code": "ALREADY_RESPONDED",
  "data": {
    "previous_response": "approved",
    "responded_at": "2025-10-10T15:30:00Z"
  }
}
```

**Validation Error (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "response": ["Response must be either 'approve' or 'reject'"],
    "notes": ["Notes field is required when rejecting"]
  }
}
```

**Business Logic**:
- Approval makes asset active and assigned to employee
- Rejection returns asset to available pool
- Same employee cannot be reassigned rejected asset for 24 hours
- Automatic notifications sent to admin on response
- Asset status updates in real-time

---

### 2. Request Asset Return
**Purpose**: Allow employee to initiate return process for an assigned asset

**Endpoint**: `POST /api/employee/assets/assignments/{assignment_id}/request-return`

**Request Body**:
```json
{
  "reason": "Project completed successfully. No longer need this equipment for daily work."
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Return request submitted successfully. Admin will review and respond.",
  "data": {
    "return_request_id": "RR_123",
    "assignment_id": 18,
    "status": "return_pending_approval",
    "requested_at": "2025-10-11T14:45:00Z",
    "asset": {
      "name": "Dell Monitor 27\"",
      "asset_tag": "MON005"
    },
    "estimated_response_time": "1-2 business days",
    "next_steps": [
      "Admin will review your request",
      "You will receive notification of approval/rejection",
      "If approved, coordinate return logistics with IT department"
    ]
  }
}
```

**Error Responses**:

**Cannot Return (422)**:
```json
{
  "success": false,
  "message": "This asset cannot be returned at this time",
  "error_code": "RETURN_NOT_ALLOWED",
  "reasons": [
    "Asset is required for your current role",
    "Return window has not opened yet",
    "Previous return request is pending"
  ]
}
```

**Business Logic**:
- Only actively assigned assets can have return requests
- Employee can provide reason and preferred return date
- Admin approval required before physical return
- Asset remains with employee until admin approves return
- Notifications sent to relevant administrators

---

## 🔔 Notification APIs

### 1. Get Notifications
**Purpose**: Retrieve asset-related notifications for the employee with filtering and pagination

**Endpoint**: `GET /api/employee/assets/notifications`

**Query Parameters**:
- `filter` (optional): Filter by read status (all, unread, read)
- `type` (optional): Filter by notification type
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `date_from` (optional): Filter from date

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "type": "asset_assigned",
        "title": "New Asset Assignment",
        "message": "MacBook Pro 16\" (LT001) has been assigned to you. Please review and respond within 24 hours.",
        "read_at": null,
        "created_at": "2025-10-11T10:00:00Z",
        "priority": "high",
        "data": {
          "assignment_id": 25,
          "asset_name": "MacBook Pro 16\"",
          "asset_tag": "LT001",
          "action_required": true,
          "action_url": "/assignments/25",
          "deadline": "2025-10-12T10:00:00Z"
        },
        "metadata": {
          "category": "assignment",
          "sender": "IT Department",
          "can_reply": false
        }
      },
      {
        "id": "550e8400-e29b-41d4-a716-446655440001",
        "type": "return_approved",
        "title": "Return Request Approved",
        "message": "Your return request for iPhone 15 Pro has been approved. Please coordinate with IT department for handover.",
        "read_at": "2025-10-10T14:30:00Z",
        "created_at": "2025-10-10T14:00:00Z",
        "priority": "medium",
        "data": {
          "assignment_id": 18,
          "asset_name": "iPhone 15 Pro",
          "asset_tag": "PH001",
          "action_required": true,
          "contact_info": {
            "department": "IT Support",
            "email": "it-support@company.com",
            "phone": "+1-234-567-8900"
          },
          "return_deadline": "2025-10-15T17:00:00Z"
        }
      }
    ],
    "pagination": {
      "total": 15,
      "unread_count": 5,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    },
    "summary": {
      "total_notifications": 15,
      "unread_notifications": 5,
      "high_priority": 2,
      "action_required": 3
    }
  }
}
```

**Notification Types**:
- `asset_assigned`: New asset assignment
- `assignment_reminder`: Reminder for pending assignment
- `assignment_approved`: Assignment was approved
- `assignment_rejected`: Assignment was rejected
- `return_requested`: Return request submitted
- `return_approved`: Return request approved
- `return_rejected`: Return request rejected
- `asset_overdue`: Asset return is overdue
- `maintenance_scheduled`: Asset maintenance notification

---

### 2. Mark Notification as Read
**Purpose**: Mark a specific notification as read

**Endpoint**: `POST /api/employee/assets/notifications/{notification_id}/read`

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Notification marked as read",
  "data": {
    "notification_id": "550e8400-e29b-41d4-a716-446655440000",
    "read_at": "2025-10-11T15:30:00Z"
  }
}
```

---

## 📱 Mobile App Integration Guidelines

### 1. Authentication Flow
```
1. App Launch → Check stored token
2. If no token or expired → Redirect to existing login system
3. User authenticates → Receive token from login API
4. Store token securely → Navigate to asset dashboard
5. Include token in all asset management requests
6. Handle token expiry → Auto-refresh or re-authenticate
```

### 2. Data Synchronization
- **Pull-to-refresh**: Implement on all list screens
- **Real-time updates**: Use polling or WebSocket for notifications
- **Offline support**: Cache critical data for offline viewing
- **Background sync**: Update data when app becomes active

### 3. Push Notifications
- **Setup**: Register device token with backend
- **Types**: Assignment notifications, reminders, approvals
- **Deep linking**: Direct users to relevant screens
- **Badge updates**: Update app icon badge with unread count

### 4. Error Handling
```javascript
// Standard error response format
{
  "success": false,
  "message": "Human readable error message",
  "error_code": "MACHINE_READABLE_CODE",
  "errors": {
    "field_name": ["Validation error messages"]
  }
}
```

### 5. Loading States
- Show loading indicators for API calls
- Implement skeleton screens for better UX
- Provide feedback for user actions
- Handle network timeout scenarios

### 6. Security Best Practices
- Store tokens in secure keychain/keystore
- Implement certificate pinning
- Validate SSL certificates
- Use HTTPS for all API calls
- Implement request/response encryption if needed

---

## 🔧 Technical Implementation

### 1. HTTP Status Codes
- `200`: Success
- `201`: Resource created
- `400`: Bad request
- `401`: Unauthorized (invalid/expired token)
- `403`: Forbidden (insufficient permissions)
- `404`: Resource not found
- `422`: Validation error
- `429`: Rate limit exceeded
- `500`: Server error

### 2. Rate Limiting
- **Standard APIs**: 60 requests per minute per user
- **Authentication**: 5 login attempts per minute per IP
- **Bulk operations**: 10 requests per minute
- **Headers included**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

### 3. Pagination
- **Standard format**: Laravel pagination
- **Default page size**: 10 items
- **Maximum page size**: 50 items
- **Metadata included**: total, current_page, last_page, has_more

### 4. Date/Time Handling
- **Format**: ISO 8601 (YYYY-MM-DDTHH:mm:ssZ)
- **Timezone**: UTC for all API responses
- **Client responsibility**: Convert to local timezone for display

### 5. File Uploads
- **Asset images**: Support for asset photos
- **Formats**: JPEG, PNG, WebP
- **Size limit**: 5MB per file
- **Multiple files**: Up to 5 images per asset

---

## 📚 API Examples

### Complete Workflow Example

#### 1. Login and Get Dashboard
```javascript
// Note: Authentication should be handled by the main app authentication system
// Assume token is already available from the main login flow

// Get Dashboard
const dashboardResponse = await fetch('/api/employee/assets/dashboard', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

#### 2. Handle Pending Assignment
```javascript
// Get pending assignments
const pendingResponse = await fetch('/api/employee/assets/pending-assignments', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

// Approve assignment
const approveResponse = await fetch(`/api/employee/assets/assignments/25/respond`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    response: 'approve',
    notes: 'Thank you for assigning this device.'
  })
});
```

#### 3. Request Return
```javascript
const returnResponse = await fetch(`/api/employee/assets/assignments/18/request-return`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    reason: 'Project completed, no longer needed',
    preferred_return_date: '2025-10-20',
    current_condition: 'good'
  })
});
```

---

## 🎯 Testing & Validation

### 1. Test Scenarios
- **Happy Path**: Complete assignment approval flow
- **Rejection Flow**: Reject assignment and verify asset availability
- **Return Flow**: Request return and track approval process
- **Error Handling**: Test invalid tokens, network errors, validation failures
- **Edge Cases**: Expired assignments, duplicate responses, permission changes

### 2. Postman Collection
The provided Postman collection includes:
- Complete authentication flow
- All API endpoints with examples
- Error scenario testing
- Environment variables setup
- Automated token management

### 3. Performance Considerations
- Implement request caching for static data
- Use pagination for large datasets
- Optimize image loading and caching
- Implement offline data storage
- Monitor API response times

---

## 📞 Support & Troubleshooting

### Common Issues

#### 1. Authentication Errors
**Problem**: Token invalid or expired
**Solution**: Implement automatic token refresh or re-login flow

#### 2. Network Connectivity
**Problem**: API calls failing due to network issues
**Solution**: Implement retry logic with exponential backoff

#### 3. Data Synchronization
**Problem**: Outdated data displayed in app
**Solution**: Implement proper cache invalidation and refresh mechanisms

### Contact Information
- **Technical Support**: dev-support@company.com
- **API Documentation**: [API Portal URL]
- **Issue Tracking**: [Support Portal URL]

---

## 🔄 Version History

### Version 1.0.0 (Current)
- Initial API implementation
- Complete asset assignment workflow
- Employee mobile app support
- Push notification system
- Comprehensive error handling

### Planned Features (Future Versions)
- Asset maintenance tracking
- QR code scanning for assets
- Bulk assignment operations
- Advanced reporting and analytics
- Integration with IoT asset tracking

---

*This documentation is maintained by the development team and updated with each API version release. For the latest updates and changes, please refer to the API changelog.*