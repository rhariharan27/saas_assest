<?php

namespace Modules\Assets\app\Enums;

enum MaintenanceType: string
{
  case REPAIR = 'repair';
  case UPGRADE = 'upgrade';
  case CLEANING = 'cleaning';
  case CALIBRATION = 'calibration';
  case SCHEDULED = 'scheduled_maintenance';
  case INSPECTION = 'inspection';
  case OTHER = 'other';

  // Optional: Helper for labels
  public function label(): string
  {
    return match ($this) {
      self::REPAIR => 'Repair',
      self::UPGRADE => 'Upgrade',
      self::CLEANING => 'Cleaning',
      self::CALIBRATION => 'Calibration',
      self::SCHEDULED => 'Scheduled Maintenance',
      self::INSPECTION => 'Inspection',
      self::OTHER => 'Other',
    };
  }
}
