<?php
/**
 * Reasons plugin for Craft CMS 3.x
 *
 * Adds conditionals to field layouts.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2020 Mats Mikkel Rummelhoff
 */

namespace mmikkel\reasons\assetbundles\reasons;

use Craft;
use craft\base\Element;
use craft\base\Volume;
use craft\elements\Asset;
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

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   Reasons
 * @since     2.0.0
 */
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
        // I'm just gonna add all element types to this, even if it only applies to Entries right now
        // If I'm lucky, this'll turn out to be future proof
        $revisionId = (int)Craft::$app->getRequest()->getParam('revisionId');
        if ($revisionId) {
            $sourceElementId = (int)Element::find()->revisionId($revisionId)->scalar();
            if ($sourceElementId && $element = Craft::$app->getElements()->getElementById($sourceElementId)) {
                $elementType = \get_class($element);
                $renderContext = null;
                if ($elementType === Entry::class) {
                    /** @var Entry $element */
                    $renderContext = "entryType:{$element->typeId}";
                } else if ($elementType === Category::class) {
                    /** @var Category $element */
                    $renderContext = "categoryGroup:{$element->groupId}";
                } else if ($elementType === Tag::class) {
                    /** @var Tag $element */
                    $renderContext = "tagGroup:{$element->groupId}";
                } else if ($elementType === GlobalSet::class) {
                    /** @var GlobalSet $element */
                    $renderContext = "globalSet:{$element->id}";
                } else if ($elementType === User::class) {
                    $renderContext = 'users';
                } else if ($elementType === Asset::class) {
                    /** @var Asset $element */
                    $renderContext = "assetSource:{$element->volumeId}";
                }
                $data['renderContext'] = $renderContext;
            }
        } else {
            // Sadly, the new Asset edit view doesn't have `<input type="hidden" name="volumeId"/>` input – so we'll need to work around that, too
            $segments = Craft::$app->getRequest()->getSegments();
            if (\count($segments) === 3 && $segments[0] === 'assets') {
                $volumeHandle = $segments[1];
                /** @var Volume $volume */
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
