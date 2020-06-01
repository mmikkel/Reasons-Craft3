<?php
/**
 * Reasons plugin for Craft CMS 3.x
 *
 * Adds conditionals to field layouts.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2020 Mats Mikkel Rummelhoff
 */

namespace mmikkel\reasons;

use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\events\FieldEvent;
use craft\events\FieldLayoutEvent;
use craft\services\Fields;
use mmikkel\reasons\assetbundles\reasons\ReasonsAssetBundle;
use mmikkel\reasons\services\ReasonsService;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\TemplateEvent;
use craft\services\Plugins;
use craft\web\View;

use yii\base\Event;

/**
 * Class Reasons
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   Reasons
 * @since     2.0.0
 *
 * @property  ReasonsService $reasons
 */
class Reasons extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Reasons
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '2.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * @var bool
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                $this->yolo();
            }
        );

        Craft::info(
            Craft::t(
                'reasons',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================
    protected function yolo()
    {

        $request = Craft::$app->getRequest();
        $user = Craft::$app->getUser()->getIdentity();

        if (!$request->getIsCpRequest() || $request->getIsSiteRequest() || $request->getIsConsoleRequest() || !$user || !$user->can('accessCp')) {
            return;
        }

        // Register services
        $this->setComponents([
            'reasons' => ReasonsService::class,
        ]);

        if ($request->getIsAjax() || $request->getAcceptsJson()) {
            $this->initAjaxRequest();
            return;
        }
        
        // Register asset bundle
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_TEMPLATE,
            function (TemplateEvent $event) {
                try {
                    Craft::$app->getView()->registerAssetBundle(ReasonsAssetBundle::class);
                } catch (InvalidConfigException $e) {
                    Craft::error(
                        'Error registering AssetBundle - '.$e->getMessage(),
                        __METHOD__
                    );
                }
            }
        );

        // Save conditionals when field layout is saved
        Event::on(
            Fields::class,
            Fields::EVENT_AFTER_SAVE_FIELD_LAYOUT,
            function (FieldLayoutEvent $event) {
                $conditionals = Craft::$app->getRequest()->getBodyParam('_reasons', null);
                if ($conditionals !== null && $fieldLayout = $event->layout) {
                    try {
                        if ($conditionals) {
                            Reasons::getInstance()->reasons->saveFieldLayoutConditionals((int)$fieldLayout->id, $conditionals);
                        } else {
                            Reasons::getInstance()->reasons->deleteFieldLayoutConditionals((int)$fieldLayout->id);
                        }
                    } catch (\Throwable $e) {
                        Craft::error($e->getMessage(), __METHOD__);
                        if (Craft::$app->getConfig()->getGeneral()->devMode) {
                            throw $e;
                        }
                    }
                }
                Reasons::getInstance()->reasons->clearCache();
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_AFTER_DELETE_FIELD_LAYOUT,
            function (FieldLayoutEvent $event) {
                Reasons::getInstance()->reasons->clearCache();
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_AFTER_SAVE_FIELD,
            function (FieldEvent $event) {
                Reasons::getInstance()->reasons->clearCache();
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_AFTER_DELETE_FIELD,
            function (FieldEvent $event) {
                Reasons::getInstance()->reasons->clearCache();
            }
        );


    }

    /**
     * @return void
     */
    protected function initAjaxRequest()
    {

        $request = Craft::$app->getRequest();
        if (!$request->getIsPost() || !$request->getIsActionRequest()) {
            return;
        }

        $actionSegments = $request->getActionSegments();
        $actionSegment = $actionSegments[\count($actionSegments) - 1] ?? null;

        if (!$actionSegment) {
            return;
        }

        if ($actionSegment === 'switch-entry-type') {
            Craft::$app->getView()->registerJs('Craft.ReasonsPlugin.initPrimaryForm();');
            return;
        }

        if ($actionSegment === 'get-editor-html') {

            $elementId = (int)$request->getBodyParam('elementId');

            if (!$elementId) {
                return;
            }

            $element = Craft::$app->getElements()->getElementById($elementId);

            if (!$element) {
                return;
            }

            $elementType = \get_class($element);
            $conditionalsKey = null;

            switch ($elementType) {
                case Entry::class:
                    $conditionalsKey = "entryType:{$element->typeId}";
                    break;

                case GlobalSet::class:
                    $conditionalsKey = "globalSet:{$element->id}";
                    break;

                case Asset::class:
                    $conditionalsKey = "assetSource:{$element->volumeId}";
                    break;

                case Category::class:
                    $conditionalsKey = "categoryGroup:{$element->groupId}";
                    break;

                case Tag::class:
                    $conditionalsKey = "tagGroup:{$element->tagId}";
                    break;

                case User::class:
                    $conditionalsKey = "users";
                    break;

                default:
                    return;

            }

            Craft::$app->getView()->registerJs("Craft.ReasonsPlugin.initElementEditor('{$conditionalsKey}');");

        }

    }

}
