<?php

namespace Modules\Assets\app\Models;

use App\Models\User;
use App\Traits\TenantTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assets\app\Enums\AssetCondition;
use Modules\Assets\app\Enums\ApprovalStatus;

class AssetAssignment extends Model
{
  use TenantTrait;

  protected $table = 'asset_assignments';

  protected $fillable = [
    'asset_id',
    'user_id',
    'assigned_at',
    'returned_at',
    'expected_return_date',
    'condition_out',
    'condition_in',
    'notes',
    'assigned_by_id',
    'received_by_id',
    'tenant_id',
    // New approval workflow fields
    'employee_approval_status',
    'employee_approval_notes',
    'employee_responded_at',
    'return_requested',
    'return_requested_at',
    'return_request_notes',
    'return_approval_status',
    'return_approval_notes',
    'return_approved_by_id',
    'return_approved_at'
  ];

  protected $casts = [
    'assigned_at' => 'datetime',
    'returned_at' => 'datetime',
    'expected_return_date' => 'date',
    'employee_responded_at' => 'datetime',
    'return_requested_at' => 'datetime',
    'return_approved_at' => 'datetime',
    'condition_out' => AssetCondition::class,
    'condition_in' => AssetCondition::class,
    'employee_approval_status' => ApprovalStatus::class,
    'return_approval_status' => ApprovalStatus::class,
    'return_requested' => 'boolean'
  ];

  // Relationships
  public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
  public function user(): BelongsTo { return $this->belongsTo(User::class); } // Assigned user
  public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by_id'); } // Admin who assigned
  public function receivedBy(): BelongsTo { return $this->belongsTo(User::class, 'received_by_id'); } // Admin who received return
  public function returnApprovedBy(): BelongsTo { return $this->belongsTo(User::class, 'return_approved_by_id'); } // Admin who approved/rejected return

  // Scopes
  public function scopeActive($query)
  {
    return $query->whereNull('returned_at');
  }

  public function scopePendingEmployeeApproval($query)
  {
    return $query->where('employee_approval_status', ApprovalStatus::PENDING);
  }

  public function scopeApprovedByEmployee($query)
  {
    return $query->where('employee_approval_status', ApprovalStatus::APPROVED);
  }

  public function scopeRejectedByEmployee($query)
  {
    return $query->where('employee_approval_status', ApprovalStatus::REJECTED);
  }

  public function scopeReturnRequested($query)
  {
    return $query->where('return_requested', true);
  }

  public function scopePendingReturnApproval($query)
  {
    return $query->where('return_requested', true)
                 ->where('return_approval_status', ApprovalStatus::PENDING);
  }

  public function scopeOverdue($query, int $days = 7)
  {
    return $query->where('assigned_at', '<', now()->subDays($days))
                 ->where('employee_approval_status', ApprovalStatus::PENDING);
  }

  // Helper Methods
  public function isPendingEmployeeApproval(): bool
  {
    return $this->employee_approval_status === ApprovalStatus::PENDING;
  }

  public function isApprovedByEmployee(): bool
  {
    return $this->employee_approval_status === ApprovalStatus::APPROVED;
  }

  public function isRejectedByEmployee(): bool
  {
    return $this->employee_approval_status === ApprovalStatus::REJECTED;
  }

  public function hasReturnRequest(): bool
  {
    return $this->return_requested === true;
  }

  public function isPendingReturnApproval(): bool
  {
    return $this->return_requested === true && 
           $this->return_approval_status === ApprovalStatus::PENDING;
  }

  public function isReturnApproved(): bool
  {
    return $this->return_approval_status === ApprovalStatus::APPROVED;
  }

  public function isOverdue(int $days = 7): bool
  {
    return $this->assigned_at->addDays($days)->isPast() && 
           $this->employee_approval_status === ApprovalStatus::PENDING;
  }

  public function getDaysWithAsset(): int
  {
    $endDate = $this->returned_at ?? now();
    return $this->assigned_at->diffInDays($endDate);
  }

  public function getDaysPendingApproval(): int
  {
    if ($this->employee_approval_status !== ApprovalStatus::PENDING) {
      return 0;
    }
    return $this->assigned_at->diffInDays(now());
  }

  public function getDaysPendingReturn(): int
  {
    if (!$this->return_requested || $this->return_approval_status !== ApprovalStatus::PENDING) {
      return 0;
    }
    return $this->return_requested_at->diffInDays(now());
  }

  public function canEmployeeRespond(): bool
  {
    return $this->employee_approval_status === ApprovalStatus::PENDING && 
           is_null($this->returned_at);
  }

  public function canRequestReturn(): bool
  {
    return $this->employee_approval_status === ApprovalStatus::APPROVED && 
           (
             !$this->return_requested || 
             $this->return_approval_status === ApprovalStatus::REJECTED
           ) &&
           is_null($this->returned_at);
  }

  public function canAdminRespondToReturn(): bool
  {
    return $this->return_requested && 
           $this->return_approval_status === ApprovalStatus::PENDING;
  }

  /**
   * Handle asset status when assignment is rejected
   */
  public function handleRejection(): void
  {
    // When assignment is rejected, make asset available again
    $this->asset->update([
      'status' => \Modules\Assets\app\Enums\AssetStatus::AVAILABLE,
      'location' => 'Available - Previous assignment rejected by ' . $this->user->getFullName()
    ]);
  }

  // Accessors for better data presentation
  public function getStatusBadgeAttribute(): string
  {
    $status = $this->employee_approval_status;
    $color = $status->color();
    $label = $status->label();
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
  }

  public function getReturnStatusBadgeAttribute(): ?string
  {
    if (!$this->return_approval_status) {
      return null;
    }
    
    $status = $this->return_approval_status;
    $color = $status->color();
    $label = "Return " . $status->label();
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
  }

  public function getFormattedAssignedAtAttribute(): string
  {
    return $this->assigned_at->format('M d, Y');
  }

  public function getFormattedReturnedAtAttribute(): ?string
  {
    return $this->returned_at?->format('M d, Y');
  }

  // protected static function newFactory() { ... }
}
