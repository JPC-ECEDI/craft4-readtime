<?php
/**
 * Read Time plugin for Craft CMS
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\models;

use jalendport\readtime\ReadTime;

use Craft;
use craft\base\Model;
use craft\helpers\DateTimeHelper;

class TimeModel extends Model
{
    // Public Properties
    // =========================================================================

    public int $seconds = 0;

    public bool $showSeconds = true;

    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return (string) $this->human();
    }

    public function human(): string
    {
        return DateTimeHelper::secondsToHumanTimeDuration($this->seconds, $this->showSeconds);
    }

    public function interval($format = '%h hours, %i minutes, %s seconds'): string
    {
        $currentTimeStamp = DateTimeHelper::currentTimeStamp();
        $datetimeStart = DateTimeHelper::toDateTime($currentTimeStamp);
        $datetimeEnd = DateTimeHelper::toDateTime(DateTimeHelper::currentTimeStamp() + $this->seconds);

        $interval = $datetimeStart->diff($datetimeEnd);

        return $interval->format($format);
    }

    public function seconds(): int
    {
        return $this->seconds;
    }

    public function minutes(): float
    {
        return floor($this->seconds / 60);
    }

    public function hours(): float
    {
        return floor(($this->seconds /  60) / 60);
    }
}
