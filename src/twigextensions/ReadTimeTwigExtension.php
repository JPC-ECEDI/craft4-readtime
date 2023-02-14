<?php
/**
 * Read Time plugin for Craft CMS
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\twigextensions;

use jalendport\readtime\ReadTime;
use jalendport\readtime\models\TimeModel;

use Craft;
use craft\helpers\StringHelper;

use Twig\TwigFilter;
use Twig\TwigFunction;
use yii\base\ErrorException;

class ReadTimeTwigExtension extends \Twig\Extension\AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'readTime';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('readTime', [$this, 'readTimeFunction']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('readTime', [$this, 'readTimeFilter']),
        ];
    }

    public function readTimeFunction($element, $showSeconds = true): TimeModel
    {
        $totalSeconds = 0;

        if ($element instanceof \craft\elements\Entry) {
            // Provided value is an entry

            foreach ($element->getFieldLayout()->getCustomFields() as $field) {
                try {
                    // If field is a matrix then loop through fields in block
                    if ($field instanceof \craft\fields\Matrix) {
                        foreach($element->getFieldValue($field->handle)->all() as $block) {
                            $blockFields = $block->getFieldLayout()->getFields();

                            foreach ($blockFields as $blockField) {
                                $value = $block->getFieldValue($blockField->handle);
                                $seconds = $this->valToSeconds($value);
                                $totalSeconds = $totalSeconds + $seconds;
                            }
                        }
                    } elseif($field instanceof \verbb\supertable\fields\SuperTableField) {
                        foreach($element->getFieldValue($field->handle)->all() as $block) {
                            $blockFields = $block->getFieldLayout()->getFields();

                            foreach ($blockFields as $blockField) {
                                if ($blockField instanceof \craft\fields\Matrix) {
                                    foreach($block->getFieldValue($blockField->handle)->all() as $matrix) {
                                        $matrixFields = $matrix->getFieldLayout()->getFields();

                                        foreach ($matrixFields as $matrixField) {
                                            $value = $matrix->getFieldValue($matrixField->handle);
                                            $seconds = $this->valToSeconds($value);
                                            $totalSeconds = $totalSeconds + $seconds;
                                        }
                                    }
                                } else {
                                    $value = $block->getFieldValue($blockField->handle);
                                    $seconds = $this->valToSeconds($value);
                                    $totalSeconds = $totalSeconds + $seconds;
                                }
                            }
                        }
                    } else {
                        $value = $element->getFieldValue($field->handle);
                        $seconds = $this->valToSeconds($value);
                        $totalSeconds = $totalSeconds + $seconds;
                    }
                } catch (ErrorException $e) {
                    continue;
                }
            }
        } elseif(is_array($element)) {
            // Provided value is a matrix field
            Craft::info('matrix field provided', 'readtime');

            foreach ($element as $block) {
                if ($block instanceof \craft\elements\MatrixBlock) {
                    $blockFields = $block->getFieldLayout()->getCustomFields();

                    foreach ($blockFields as $blockField) {
                        $value = $block->getFieldValue($blockField->handle);
                        $seconds = $this->valToSeconds($value);
                        $totalSeconds = $totalSeconds + $seconds;
                    }
                }
            }
        }

        $data = [
            'seconds'     => $totalSeconds,
            'showSeconds' => $showSeconds,
        ];

        return new TimeModel($data);
    }

    public function readTimeFilter($value = null, $showSeconds = true): TimeModel
    {
        $seconds = $this->valToSeconds($value);

        $data = [
            'seconds'     => $seconds,
            'showSeconds' => $showSeconds,
        ];

        return new TimeModel($data);
    }

    // Private Methods
    // =========================================================================

    private function valToSeconds($value): float
    {
        $settings = ReadTime::$plugin->getSettings();
        $wpm = $settings->wordsPerMinute;

        $string = StringHelper::toString($value);
        $wordCount = StringHelper::countWords($string);
        return floor($wordCount / $wpm * 60);
    }
}
