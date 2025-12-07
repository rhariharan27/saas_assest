<?php

namespace Modules\Assets\app\Models;

use App\Traits\TenantTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
  use TenantTrait;

  protected $table = 'asset_categories';

  protected $fillable = [
    'name',
    'description',
    'tenant_id',
    'is_active'
  ];

  protected $casts =[
    'is_active' => 'boolean',
  ];

  /**
   * Get the assets belonging to this category.
   */
  public function assets(): HasMany
  {
    return $this->hasMany(Asset::class);
  }
}
