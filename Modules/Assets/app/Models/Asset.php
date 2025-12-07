<?php

namespace Modules\Assets\app\Models;

use App\Models\User; // Core User model
use App\Traits\TenantTrait;
use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Assets\app\Enums\AssetCondition;
use Modules\Assets\app\Enums\AssetStatus;

class Asset extends Model
{
  use SoftDeletes, TenantTrait, UserActionsTrait, HasUuids;

  protected $table = 'assets';

  protected $fillable = [
    'uuid',
    'name',
    'asset_tag',
    'asset_category_id',
    'manufacturer',
    'model',
    'serial_number',
    'purchase_date',
    'purchase_cost',
    'supplier',
    'warranty_expiry_date',
    'status',
    'condition',
    'location',
    'notes',
    'tenant_id',
    'created_by_id', // Needed if UserActionsTrait not used/configured
    'updated_by_id', // Needed if UserActionsTrait not used/configured
  ];

  protected $casts = [
    'purchase_date' => 'date',
    'warranty_expiry_date' => 'date',
    'purchase_cost' => 'decimal:2',
    'status' => AssetStatus::class, // Cast to Enum
    'condition' => AssetCondition::class, // Cast to Enum
  ];

  public function uniqueIds(): array { return ['uuid']; }

  // Relationships
  public function category(): BelongsTo { return $this->belongsTo(AssetCategory::class, 'asset_category_id'); }
  public function assignments(): HasMany { return $this->hasMany(AssetAssignment::class)->orderBy('assigned_at', 'desc'); } // Order history
  public function maintenances(): HasMany { return $this->hasMany(AssetMaintenance::class)->orderBy('performed_at', 'desc'); } // Order history
  public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_id'); }
  public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_id'); }

  public function activities(): HasMany
  {
    return $this->hasMany(AssetActivity::class)->orderBy('created_at', 'desc');
  }

  /**
   * Get the current assignment record for this asset (where returned_at is null).
   */
  public function currentAssignment(): HasOne
  {
    return $this->hasOne(AssetAssignment::class)->whereNull('returned_at')->latestOfMany('assigned_at'); // Ensure only latest if multiple somehow exist
  }

  // Accessors & Helpers
  public function getCurrentAssigneeAttribute(): ?User
  {
    // Access the user through the currentAssignment relationship
    return $this->currentAssignment?->user; // Returns User model or null
  }

  public function isAvailable(): bool
  {
    return $this->status === AssetStatus::AVAILABLE;
  }

  public function isAssigned(): bool
  {
    return $this->status === AssetStatus::ASSIGNED;
  }

  /**
   * Check if asset has any active assignments (excluding rejected ones)
   */
  public function hasActiveAssignment(): bool
  {
    return $this->assignments()
      ->whereNull('returned_at')
      ->where('employee_approval_status', '!=', \Modules\Assets\app\Enums\ApprovalStatus::REJECTED)
      ->exists();
  }

  /**
   * Get current active assignment (excluding rejected ones)
   */
  public function getCurrentAssignment(): ?AssetAssignment
  {
    return $this->assignments()
      ->whereNull('returned_at')
      ->where('employee_approval_status', '!=', \Modules\Assets\app\Enums\ApprovalStatus::REJECTED)
      ->first();
  }

  /**
   * Check if asset is available for new assignment
   */
  public function isAvailableForAssignment(): bool
  {
    return $this->status === AssetStatus::AVAILABLE && !$this->hasActiveAssignment();
  }

  /**
   * Check if asset has any rejected assignments
   */
  public function hasRejectedAssignments(): bool
  {
    return $this->assignments()
      ->whereNull('returned_at')
      ->where('employee_approval_status', \Modules\Assets\app\Enums\ApprovalStatus::REJECTED)
      ->exists();
  }

  /**
   * Get the most recent rejected assignment
   */
  public function getLastRejectedAssignment(): ?AssetAssignment
  {
    return $this->assignments()
      ->whereNull('returned_at')
      ->where('employee_approval_status', \Modules\Assets\app\Enums\ApprovalStatus::REJECTED)
      ->orderBy('employee_responded_at', 'desc')
      ->first();
  }

  // protected static function newFactory() { ... }
}
