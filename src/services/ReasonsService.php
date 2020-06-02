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

use Craft;
use craft\db\Query;
use craft\base\Component;
use craft\base\FieldInterface;
use craft\elements\User;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
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
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
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
     * Saves a field layout's conditionals, via the Project Config
     *
     * @param FieldLayout $layout
     * @param string|array $conditionals
     * @return bool
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function saveFieldLayoutConditionals(FieldLayout $layout, $conditionals): bool
    {

        $uid = (new Query())
            ->select(['uid'])
            ->from('{{%reasons}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->scalar();

        $isNew = !$uid;
        if ($isNew) {
            $uid = StringHelper::UUID();
        }

        $conditionals = $this->prepConditionalsForProjectConfig($conditionals);

        // Save it to the project config
        $path = "reasons_conditionals.{$uid}";
        Craft::$app->projectConfig->set($path, [
            'fieldLayoutUid' => $layout->uid,
            'conditionals' => $conditionals,
        ]);

        return true;
    }

    /**
     * Deletes a field layout's conditionals, via the Project Config
     *
     * @param FieldLayout $layout
     * @return bool
     */
    public function deleteFieldLayoutConditionals(FieldLayout $layout): bool
    {

        $uid = (new Query())
            ->select(['uid'])
            ->from('{{%reasons}}')
            ->where(['fieldLayoutId' => $layout->id])
            ->scalar();

        if (!$uid) {
            return false;
        }

        // Remove it from the project config
        $path = "reasons_conditionals.{$uid}";
        Craft::$app->projectConfig->remove($path);

        return true;
    }

    /**
     * @param ConfigEvent $event
     * @throws \yii\db\Exception
     */
    public function onProjectConfigChange(ConfigEvent $event)
    {

        $uid = $event->tokenMatches[0];

        $id = (new Query())
            ->select(['id'])
            ->from('{{%reasons}}')
            ->where(['uid' => $uid])
            ->scalar();

        $isNew = empty($id);

        if ($isNew) {
            $fieldLayoutId = (int)Db::idByUid('{{%fieldlayouts}}', $event->newValue['fieldLayoutUid']);
            Craft::$app->db->createCommand()
                ->insert('{{%reasons}}', [
                    'fieldLayoutId' => $fieldLayoutId,
                    'conditionals' => $event->newValue['conditionals'],
                    'uid' => $uid,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%reasons}}', [
                    'conditionals' => $event->newValue['conditionals'],
                ], ['id' => $id])
                ->execute();
        }

        $this->clearCache();

    }

    /**
     * @param ConfigEvent $event
     * @throws \yii\db\Exception
     */
    public function onProjectConfigDelete(ConfigEvent $event)
    {

        $uid = $event->tokenMatches[0];

        $id = (new Query())
            ->select(['id'])
            ->from('{{%reasons}}')
            ->where(['uid' => $uid])
            ->scalar();

        if (!$id) {
            return;
        }

        Craft::$app->db->createCommand()
            ->delete('{{%reasons}}', ['id' => $id])
            ->execute();

        $this->clearCache();

    }

    /**
     * @param RebuildConfigEvent $event
     * @return void
     */
    public function onProjectConfigRebuild(RebuildConfigEvent $event)
    {
        $rows = (new Query())
            ->select(['reasons.uid', 'reasons.conditionals', 'fieldlayouts.uid AS fieldLayoutUid'])
            ->from('{{%reasons}} AS reasons')
            ->innerJoin('{{%fieldlayouts}} AS fieldlayouts', 'fieldlayouts.id = reasons.fieldLayoutId')
            ->all();

        foreach ($rows as $row) {
            $uid = $row['uid'];
            $path = "reasons_conditionals.{$uid}";
            $event->config[$path]['conditionals'] = $row['conditionals'];
            $event->config[$path]['fieldLayoutUid'] = $row['fieldLayoutUid'];
        }

        $this->clearCache();
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
     * @param string|array $conditionals
     * @return string|null
     */
    protected function prepConditionalsForProjectConfig($conditionals)
    {
        if (!$conditionals) {
            return null;
        }
        $return = [];
        $conditionals = Json::decodeIfJson($conditionals);
        foreach ($conditionals as $targetFieldId => $statements) {
            $targetFieldUid = Db::uidById('{{%fields}}', $targetFieldId);
            $return[$targetFieldUid] = \array_map(function (array $rules) {
                return \array_map(function (array $rule) {
                    return [
                        'field' => Db::uidById('{{%fields}}', $rule['fieldId']),
                        'compare' => $rule['compare'],
                        'value' => $rule['value'],
                    ];
                }, $rules);
            }, $statements);
        }
        return Json::encode($return);
    }

    /**
     * @param string|array $conditionals
     * @return array|null
     */
    protected function normalizeConditionalsFromProjectConfig($conditionals)
    {
        if (!$conditionals) {
            return null;
        }
        $return = [];
        $conditionals = Json::decodeIfJson($conditionals);
        foreach ($conditionals as $targetFieldUid => $statements) {
            $targetFieldId = Db::idByUid('{{%fields}}', $targetFieldUid);
            $return[$targetFieldId] = \array_map(function (array $rules) {
                return \array_map(function (array $rule) {
                    return [
                        'fieldId' => Db::idByUid('{{%fields}}', $rule['field']),
                        'compare' => $rule['compare'],
                        'value' => $rule['value'],
                    ];
                }, $rules);
            }, $statements);
        }
        return $return;
    }

    /**
     * Returns all conditionals, mapped by source key
     *
     * @return array
     */
    protected function getConditionals(): array
    {

        // Get all conditionals from database
        $rows = (new Query())
            ->select(['reasons.id', 'reasons.fieldLayoutId', 'reasons.conditionals'])
            ->from('{{%reasons}} AS reasons')
            ->innerJoin('{{%fieldlayouts}} AS fieldlayouts', 'fieldlayouts.id = fieldLayoutId')
            ->all();

        if (!$rows) {
            return [];
        }

        // Map conditionals to field layouts, and convert field uids to ids
        $conditionals = [];
        foreach ($rows as $row) {
            $conditionals["fieldLayout:{$row['fieldLayoutId']}"] = $this->normalizeConditionalsFromProjectConfig($row['conditionals']);
        }

        // Map conditionals to sources
        $conditionalsBySources = [];
        $sources = $this->getSources();
        foreach ($sources as $sourceId => $fieldLayoutId) {
            if (!isset($conditionals["fieldLayout:{$fieldLayoutId}"])) {
                continue;
            }
            $conditionalsBySources[$sourceId] = $conditionals["fieldLayout:{$fieldLayoutId}"];
        }

        return $conditionalsBySources;
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
        $reasons = Reasons::getInstance();
        return \implode('-', [
            $reasons->getHandle(),
            $reasons->getVersion(),
            $reasons->schemaVersion
        ]);
    }
}
