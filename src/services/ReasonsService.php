<?php
/**
 * Reasons plugin for Craft CMS 3.x
 *
 * Adds conditionals to field layouts.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2020 Mats Mikkel Rummelhoff
 */

namespace mmikkel\reasons\services;

use mmikkel\reasons\Reasons;
use mmikkel\reasons\records\Conditionals;

use Craft;
use craft\base\Component;
use craft\base\FieldInterface;
use craft\elements\User;

use craft\fields\Assets;
use craft\fields\Categories;
use craft\fields\Checkboxes;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\Number;
use craft\fields\Lightswitch;
use craft\fields\MultiSelect;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Tags;
use craft\fields\Users;

use craft\records\EntryType;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   Reasons
 * @since     2.0.0
 */
class ReasonsService extends Component
{

    /** @var int */
    const CACHE_TTL = 1800;

    /** @var array */
    protected $allFields;

    /** @var array */
    protected $sources;

    // Public Methods
    // =========================================================================

    /**
     * @param int $fieldLayoutId
     * @param $conditionals
     * @return bool
     */
    public function saveFieldLayoutConditionals(int $fieldLayoutId, $conditionals): bool
    {
        $record = new Conditionals();
        $record->fieldLayoutId = $fieldLayoutId;
        $record->conditionals = $conditionals;
        return $record->save();
    }

    /**
     * Clears Reasons' data caches
     *
     * @return void
     */
    public function clearCache()
    {
        Craft::$app->getCache()->delete($this->getCacheKey());
    }

    /**
     * @return array|mixed
     */
    public function getData()
    {
        $doCacheData = !Craft::$app->getConfig()->getGeneral()->devMode;
        $cacheKey = $this->getCacheKey();

        if ($doCacheData && $data = Craft::$app->getCache()->get($cacheKey)) {
            return $data;
        }

        $data = [
            'conditionals' => $this->getConditionals(),
            'toggleFieldTypes' => $this->getToggleFieldTypes(),
            'toggleFields' => $this->getToggleFields(),
            'fieldIds' => $this->getFieldIdsByHandle(),
        ];

        if ($doCacheData) {
            Craft::$app->getCache()->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getConditionals(): array
    {

        $return = [];
        $sources = $this->getSources();

        // Get all conditionals, map them to field layout IDs
        $conditionals = [];
        $conditionalsRecords = Conditionals::find()->all();
        /** @var Conditionals $conditionalsRecord */
        foreach ($conditionalsRecords as $conditionalsRecord) {
            $conditionals["fieldLayout:{$conditionalsRecord->fieldLayoutId}"] = $conditionalsRecord->conditionals;
        }

        // Map conditionals to sources
        foreach ($sources as $sourceId => $fieldLayoutId) {
            if (!isset($conditionals["fieldLayout:{$fieldLayoutId}"])) {
                continue;
            }
            $return[$sourceId] = $conditionals["fieldLayout:{$fieldLayoutId}"];
        }

        return $return;
    }

    /**
     * @return array
     */
    protected function getSources(): array
    {

        if (!isset($this->sources)) {

            $sources = [];

            $entryTypeRecords = EntryType::find()->all();
            foreach ($entryTypeRecords as $entryTypeRecord) {
                $sources["entryType:{$entryTypeRecord->id}"] = (int)$entryTypeRecord->fieldLayoutId;
                $sources["section:{$entryTypeRecord->sectionId}"] = (int)$entryTypeRecord->fieldLayoutId;
            }

            $categoryGroups = Craft::$app->getCategories()->getAllGroups();
            foreach ($categoryGroups as $categoryGroup) {
                $sources["categoryGroup:{$categoryGroup->id}"] = (int)$categoryGroup->fieldLayoutId;
            }

            $tagGroups = Craft::$app->getTags()->getAllTagGroups();
            foreach ($tagGroups as $tagGroup) {
                $sources["tagGroup:{$tagGroup->id}"] = (int)$tagGroup->fieldLayoutId;
            }

            $volumes = Craft::$app->getVolumes()->getAllVolumes();
            foreach ($volumes as $volume) {
                $sources["assetSource:{$volume->id}"] = (int)$volume->fieldLayoutId;
            }

            $globalSets = Craft::$app->getGlobals()->getAllSets();
            foreach ($globalSets as $globalSet) {
                $sources["globalSet:{$globalSet->id}"] = (int)$globalSet->fieldLayoutId;
            }

            $usersFieldLayout = Craft::$app->getFields()->getLayoutByType(User::class);
            if ($usersFieldLayout) {
                $sources['users'] = (int)$usersFieldLayout->id;
            }

            // Solspace Calendar - TODO
            /*$solspaceCalendarPlugin = craft()->plugins->getPlugin('calendar');
            if ($solspaceCalendarPlugin && $solspaceCalendarPlugin->getDeveloper() === 'Solspace') {
                // Before 1.7.0, Solspace Calendar used a single Field Layout for all calendars. Let's try and support both the old and the new
                if (version_compare($solspaceCalendarPlugin->getVersion(), '1.7.0', '>=')) {
                    $solspaceCalendars = craft()->calendar_calendars->getAllCalendars();
                    if ($solspaceCalendars && is_array($solspaceCalendars) && !empty($solspaceCalendars)) {
                        foreach ($solspaceCalendars as $solspaceCalendar) {
                            $sources['solspaceCalendar:'.$solspaceCalendar->id] = $solspaceCalendar->fieldLayoutId;
                        }
                    }
                } else {
                    $solspaceCalendarFieldLayout = craft()->fields->getLayoutByType('Calendar_Event');
                    if ($solspaceCalendarFieldLayout) {
                        $sources['solspaceCalendar'] = $solspaceCalendarFieldLayout->id;
                    }
                }
            }*/

            $this->sources = $sources;

        }

        return $this->sources;
    }

    /**
     * Returns all toggleable fields
     *
     * @return array
     */
    protected function getToggleFields(): array
    {
        $toggleFieldTypes = $this->getToggleFieldTypes();
        $toggleFields = [];
        $fields = $this->getAllFields();
        /** @var FieldInterface $field */
        foreach ($fields as $field) {
            $fieldType = \get_class($field);
            if (!\in_array($fieldType, $toggleFieldTypes)) {
                continue;
            }
            $toggleFields[] = [
                'id' => (int)$field->id,
                'handle' => $field->handle,
                'name' => $field->name,
                'type' => $fieldType,
                'settings' => $field->getSettings(),
            ];
        }
        return $toggleFields;
    }

    /**
     * Returns all toggleable fieldtype classnames
     *
     * @return string[]
     */
    protected function getToggleFieldTypes(): array
    {
        // TODO PositionSelect (now a plugin)
        // TODO Third party field types
        return [
            Lightswitch::class,
            Dropdown::class,
            Checkboxes::class,
            MultiSelect::class,
            RadioButtons::class,
            Number::class,
            PlainText::class,
            Entries::class,
            Categories::class,
            Tags::class,
            Assets::class,
            Users::class,
        ];
    }

    /**
     * Returns all global field IDs, indexed by handle
     *
     * @return array
     */
    protected function getFieldIdsByHandle(): array
    {
        $handles = [];
        $fields = $this->getAllFields();
        foreach ($fields as $field) {
            $handles[$field->handle] = (int)$field->id;
        }
        return $handles;
    }

    /**
     * @return FieldInterface[]
     */
    protected function getAllFields(): array
    {
        if (!isset($this->allFields)) {
            $this->allFields = Craft::$app->getFields()->getAllFields('global');
        }
        return $this->allFields;
    }

    /**
     * @return string
     */
    protected function getCacheKey(): string
    {
        return Reasons::getInstance()->getHandle() . '-' . Reasons::getInstance()->getVersion() . '-' . Reasons::getInstance()->schemaVersion;
    }
}
