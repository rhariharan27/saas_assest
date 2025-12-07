<?php

namespace Modules\Assets\app\Models; // User's preferred namespace

use App\Models\User; // Core User model path
use App\Traits\TenantTrait; // Assuming global trait path
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssetActivity extends Model
{
  use TenantTrait;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'asset_activities';

  /**
   * Disable 'updated_at' timestamp for log entries.
   */
  const UPDATED_AT = null;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'asset_id',
    'user_id',           // User performing action
    'related_user_id',   // User involved (e.g., assignee)
    'related_model_type',
    'related_model_id',
    'action',
    'details',
    'tenant_id',
    'created_at',        // Allow setting specific creation time if needed
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'created_at' => 'datetime', // Ensure created_at is treated as Carbon instance
  ];

  // Relationships

  /**
   * Get the asset this activity belongs to.
   */
  public function asset(): BelongsTo
  {
    return $this->belongsTo(Asset::class);
  }

  /**
   * Get the user who performed the action (the actor).
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Get the user who was involved or subject of the action (e.g., assignee).
   */
  public function relatedUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'related_user_id');
  }

  /**
   * Get the related model instance (e.g., AssetAssignment or AssetMaintenance).
   * Optional: Only useful if you need to link back directly from the log.
   */
  public function relatedModel(): MorphTo
  {
    return $this->morphTo();
  }

  // Optional: Factory if needed for testing/seeding
  // protected static function newFactory()
  // {
  //     return \Modules\Assets\Database\factories\AssetActivityFactory::new();
  // }
}
