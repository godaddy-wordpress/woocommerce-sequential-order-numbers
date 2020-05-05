# WooCommerce Jilt Promotions
A self-contained package for WooCommerce extensions to promote Jilt services.

## Requirements
- PHP 5.6+
- WooCommerce 3.0+

## Installation

1. Require via composer:
    ```json
    {
        "repositories": [
            {
              "type": "vcs",
              "url": "https://github.com/skyverge/wc-jilt-promotions"
            }
        ],
        "require": {
            "skyverge/wc-jilt-promotions": "1.0.0"
        }
    }
    ```
1. Require the loader:
    ```php
    require_once( 'vendor/skyverge/wc-jilt-promotions/load.php' );
    ```
    - Must be required _before_ `plugins_loaded`
    - Must be required _after_ any environmental checks, like PHP version (5.6+) or WooCommerce active/version checks

## Components
For now we have a single **Emails** component, and more components can be added in the future.

### Emails
Adds call-out to email settings pages informing users of advanced email capabilities with Jilt. Allows one-click install of the Jilt for WooCommerce plugin.

![](https://p-b1Flee.t1.n0.cdn.getcloudapp.com/items/OAubn0JN/Screen%20Shot%202020-05-01%20at%209.19.12%20AM.png?v=e6da2f0ff9803ebdb80230a30b056fda)

#### Customization

##### Filters

- `sv_wc_jilt_prompt_should_display` - Filters whether the email prompt should be displayed. This applies to all screens.
- `sv_wc_jilt_prompt_should_display_for_email` - Filters whether the prompt should be displayed for a particular `\WC_Email` object
- `sv_wc_jilt_prompt_email_ids` - Filters an array of email IDs that should display the prompt.
- `sv_wc_jilt_prompt_description` - Filters the prompt's description. Allows actors to adjust messaging depending on the `$email_id`
- `sv_wc_jilt_prompt_default_description` - Filter's the prompt's default/fallback description, if not specified for a particular email
- `sv_wc_jilt_general_prompt_description` - Filters the general prompt description, displayed on the global Emails setting page

## Development

* Compile assets: `gulp compile`
