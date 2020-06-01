# Reasons plugin for Craft CMS 3.x

_Supercharge your field layouts with conditionals._  

Inspired by Advanced Custom Fields for WordPress, Reasons adds simple conditionals to field layouts – making it possible to hide or show fields on the fly, as content is being edited.  

## Requirements

This plugin requires Craft CMS 3.4.22.1 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require mmikkel/reasons

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Reasons.

## WTF?

Yeah, [I wasn't gonna do this](https://github.com/mmikkel/Reasons-Craft/wiki/Statement-on-Reasons-2,-Matrix-and-Craft-3), but I finally ported this plugin to Craft 3, mainly due to a couple of Craft 2 migrations that suffered a bit without it, in terms of authoring experience.  

Reasons for Craft 3 is basically the exact same plugin as for [Craft 2](https://github.com/mmikkel/Reasons-Craft). I spent very little time on the port; basically just copy-pasted the JavaScript and made sure it works with the new Drafts and Preview system. It does *not* support Project Config, and probably never will. I've also removed support for all custom fieldtypes/plugins, including Craft Commerce.  

_I'm not likely to do any additional development on this plugin, beyond making sure that it works as-is with upcoming Craft 3.x releases (if possible)._  

**I'd discourage using this plugin for new projects; it's main purpose is to make it easier to port existing Craft 2 sites already using the Craft 2 version. If you do decide use it, consider it as a stop-gap solution until Craft has conditionals in core (currently in the Craft 4 roadmap, ETA 2021).**

## Updating from Craft 2

Existing conditionals in the database from the Craft 2 version should be migrated over automatically and continue to work as-is. Note that all third-party fieldtype and plugin support (including Solspace Calendar and Craft Commerce) has been removed, though.    
