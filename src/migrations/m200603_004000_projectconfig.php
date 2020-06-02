<?php


namespace mmikkel\reasons\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m200603_004000_projectconfig extends Migration
{

    /**
     * @inheritDoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $schemaVersion = Craft::$app->projectConfig
            ->get('plugins.reasons.schemaVersion', true);

        if (\version_compare($schemaVersion, '2.1.0', '<')) {
            
            $rows = (new Query())
                ->select(['reasons.fieldLayoutId', 'reasons.conditionals', 'reasons.uid', 'fieldlayouts.uid AS fieldLayoutUid'])
                ->innerJoin('{{%fieldlayouts}} AS fieldlayouts', 'fieldlayouts.id = reasons.fieldLayoutId')
                ->from('{{%reasons}} AS reasons')
                ->all();
            
            foreach ($rows as $row) {
                $path = "reasons_conditionals.{$row['uid']}";
                Craft::$app->projectConfig->set($path, [
                    'fieldLayoutUid' => $row['fieldLayoutUid'],
                    'conditionals' => $this->prepConditionalsForProjectConfig($row['conditionals']),
                ]);
            }
            
        }
        
        return true;
    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        echo "m200603_004000_projectconfig cannot be reverted.\n";
        return false;
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
        foreach ($conditionals as $targetFieldIdOrUid => $statements) {
            if (!StringHelper::isUUID($targetFieldIdOrUid)) {
                $targetFieldUid = Db::uidById('{{%fields}}', $targetFieldIdOrUid);
            } else {
                $targetFieldUid = $targetFieldIdOrUid;
            }
            $return[$targetFieldUid] = \array_map(function (array $rules) {
                return \array_map(function (array $rule) {
                    $fieldIdOrUid = $rule['fieldId'];
                    if (!StringHelper::isUUID($fieldIdOrUid)) {
                        $fieldUid = Db::uidById('{{%fields}}', $fieldIdOrUid);
                    } else {
                        $fieldUid = $fieldIdOrUid;
                    }
                    return [
                        'field' => $fieldUid,
                        'compare' => $rule['compare'],
                        'value' => $rule['value'],
                    ];
                }, $rules);
            }, $statements);
        }
        return Json::encode($return);
    }

}
