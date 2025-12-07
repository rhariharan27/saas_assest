<?php

namespace Modules\Assets\app\Enums;

enum AssetStatus: string
{
  case AVAILABLE = 'available';
  case ASSIGNED = 'assigned';
  case IN_REPAIR = 'in_repair';
  case DAMAGED = 'damaged';
  case LOST = 'lost';
  case DISPOSED = 'disposed';
  case ARCHIVED = 'archived'; // Alternative to disposed maybe

  // Optional: Helper for labels
  public function label(): string
  {
    return match ($this) {
      self::AVAILABLE => 'Available',
      self::ASSIGNED => 'Assigned',
      self::IN_REPAIR => 'In Repair',
      self::DAMAGED => 'Damaged',
      self::LOST => 'Lost/Stolen',
      self::DISPOSED => 'Disposed',
      self::ARCHIVED => 'Archived',
    };
  }

  // Helper for badge colors
  public function color(): string
  {
    return match ($this) {
      self::AVAILABLE => 'success',
      self::ASSIGNED => 'primary',
      self::IN_REPAIR => 'warning',
      self::DAMAGED => 'danger',
      self::LOST => 'danger',
      self::DISPOSED => 'secondary',
      self::ARCHIVED => 'secondary',
    };
  }
}
