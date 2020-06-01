<?php
/**
 * Reasons plugin for Craft CMS 3.x
 *
 * Adds conditionals to field layouts.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2020 Mats Mikkel Rummelhoff
 */

namespace mmikkel\reasons\records;

use mmikkel\reasons\Reasons;

use Craft;
use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $fieldLayoutId
 * @property string $conditionals
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   Reasons
 * @since     2.0.0
 */
class Conditionals extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%reasons}}';
    }
}
