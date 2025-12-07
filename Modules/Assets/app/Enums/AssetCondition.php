<?php

namespace Modules\Assets\app\Enums;

enum AssetCondition: string
{
  case NEW = 'new';
  case GOOD = 'good';
  case FAIR = 'fair';
  case POOR = 'poor';
  case BROKEN = 'broken'; // For repair/disposal

  // Optional: Helper for labels
  public function label(): string
  {
    return match ($this) {
      self::NEW => 'New',
      self::GOOD => 'Good',
      self::FAIR => 'Fair',
      self::POOR => 'Poor',
      self::BROKEN => 'Broken/Needs Repair',
    };
  }

  // Helper for badge colors
  public function color(): string
  {
    return match ($this) {
      self::NEW => 'success',
      self::GOOD => 'success',
      self::FAIR => 'warning',
      self::POOR => 'danger',
      self::BROKEN => 'danger',
    };
  }
}
