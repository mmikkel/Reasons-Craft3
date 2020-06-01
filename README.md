# Reasons plugin for Craft CMS 3.x

_Supercharge your field layouts with conditionals._  

Inspired by Advanced Custom Fields for WordPress, Reasons adds simple conditionals to field layouts – making it possible to hide or show fields on the fly, as content is being edited.  

**Using this plugin for new projects is discouraged – its main purpose is to make it easier to port existing Craft 2 sites already using the Craft 2 version, over to Craft 3. If you do decide use it, consider it a stop-gap solution until Craft has conditionals in core (currently in the Craft 4 roadmap, ETA 2021).**  

[Looking for the Craft 2 version?](https://github.com/mmikkel/Reasons-Craft)  

### Changes from the Craft 2 version:  

* Works with Craft 3.4+
* Works with the new Drafts and Preview systems, and the new Asset edit pages in Craft 3.4
* Third-party fieldtypes or plugins (including Craft Commerce) are *not* supported

_Reasons for Craft 3 does not work with Project Config, and it's very unlikely that I'll ever add support for it._

## Requirements

**This plugin requires Craft CMS 3.4.22.1 or later.**

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require mmikkel/reasons

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Reasons.

## WTF?

Yeah, [I wasn't gonna do this](https://github.com/mmikkel/Reasons-Craft/wiki/Statement-on-Reasons-2,-Matrix-and-Craft-3).  

Reasons for Craft 3 is basically the exact same plugin as [Reasons for Craft 2](https://github.com/mmikkel/Reasons-Craft). I spent very little time on the port; basically just copy-pasted the JavaScript and made sure it works with the new Drafts and Preview system in Craft 3s.  

_I'm not likely to do any additional development on this plugin, beyond making sure that it works as-is with upcoming Craft 3.x releases (if possible)._ Consider it on life support and a stop-gap solution until Pixel & Tonic [finally add conditionals to core](https://github.com/craftcms/cms/issues/805).

## Updating from Craft 2

Existing conditionals in the database from the Craft 2 version should be migrated over automatically and continue to work as-is. Note that all third-party fieldtype and plugin support (including Solspace Calendar and Craft Commerce) has been removed, though.    
