<?php

namespace mmikkel\reasons\assetbundles\reasons;

use Craft;
use craft\base\Element;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;
use mmikkel\reasons\Reasons;

class ReasonsAssetBundle extends AssetBundle
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@mmikkel/reasons/assetbundles/reasons/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/reasons.js',
            'js/builder.js',
            'js/fld.js',
            'js/render.js',
        ];

        $this->css = [
            'css/reasons.css',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        $data = Reasons::getInstance()->reasons->getData();

        // Check if there's a revisionId in the GET params
        // We have to do this to account for Craft's new draft/revision system, and the new DraftEditor JS component
        $revisionId = (int)Craft::$app->getRequest()->getParam('revisionId');
        if ($revisionId) {
            $sourceElementId = (int)Element::find()->revisionId($revisionId)->scalar();
            if ($sourceElementId && $element = Craft::$app->getElements()->getElementById($sourceElementId)) {
                $elementType = \get_class($element);
                $elementContext = null;
                if ($elementType === Entry::class) {
                    /** @var Entry $element */
                    $elementContext = "entryType:{$element->typeId}";
                } else if ($elementType === Category::class) {
                    /** @var Category $element */
                    $elementContext = "categoryGroup:{$element->groupId}";
                } else if ($elementType === Tag::class) {
                    /** @var Tag $element */
                    $elementContext = "tagGroup:{$element->groupId}";
                } else if ($elementType === GlobalSet::class) {
                    /** @var GlobalSet $element */
                    $elementContext = "globalSet:{$element->id}";
                } else if ($elementType === User::class) {
                    $elementContext = 'users';
                }
                $data['renderContext'] = $elementContext;
            }
        } else {
            // Sadly, the new Asset edit view doesn't have `<input type="hidden" name="volumeId"/>` input – so we'll need to work around that, too
            $segments = Craft::$app->getRequest()->getSegments();
            if (\count($segments) === 3 && $segments[0] === 'assets') {
                $volumeHandle = $segments[1];
                $volume = Craft::$app->getVolumes()->getVolumeByHandle($volumeHandle);
                if ($volume) {
                    $data['renderContext'] = "assetSource:{$volume->id}";
                }
            }
        }

        $json = Json::encode($data, JSON_UNESCAPED_UNICODE);
        $js = <<<JS
Craft.ReasonsPlugin.init({$json});
JS;
        $view->registerJs($js, View::POS_END);
    }

}
