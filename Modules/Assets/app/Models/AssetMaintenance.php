<?php

namespace Modules\Assets\app\Models;

use App\Models\User;
use App\Traits\TenantTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assets\app\Enums\MaintenanceType;

class AssetMaintenance extends Model
{
  use TenantTrait;

  protected $table = 'asset_maintenances';

  protected $fillable = [
    'asset_id',
    'maintenance_type',
    'performed_at',
    'cost',
    'provider',
    'details',
    'next_due_date',
    'completed_by_id',
    'tenant_id',
  ];

  protected $casts = [
    'performed_at' => 'datetime',
    'next_due_date' => 'date',
    'cost' => 'decimal:2',
    'maintenance_type' => MaintenanceType::class, // Cast to Enum
  ];

  // Relationships
  public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
  public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by_id'); } // User who logged/completed

}
