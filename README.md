# Diglin GoogleAnalytics

- Overwrite Magento GoogleAnalytics and make it compatible with the new Universal Analytics 
- Add category in the ecommerce transaction
- Support of all order transaction (online/offline) based on [Hackathon project](https://github.com/magento-hackathon/UniversalGaConversionTracking) but with lots of big fixes and improvements.

## Requirements
- Default Magento Google Analytics
- Magento >= 1.7.1 until 1.9.x (not tested on earlier version but may/should work)

## Installation

### Via modman
- Install [modman](https://github.com/colinmollenhour/modman)
- Use the command from your Magento installation folder: `modman clone https://github.com/diglin/Diglin_GoogleAnalytics.git`

### Via composer
- Install [composer](http://getcomposer.org/download/)
- Create a composer.json into your project like the following sample:

```json
{
    ...
    "require": {
        "diglin/diglin_googleanalytics":"1.*"
    },
    "repositories": [
	    {
            "type": "composer",
            "url": "http://packages.firegento.com"
        }
    ],
    "extra":{
        "magento-root-dir": "./"
    }
}

```

- Then from your composer.json folder: `php composer.phar install` or `composer install`

### Manually
- You can copy the files from the folders of this repository to the same folders of your installation

## Author

* Sylvain Rayé
* http://www.diglin.com/
* [@diglin_](https://twitter.com/diglin_)
* [Follow me on github!](https://github.com/diglin)

## Thanks

- To the authors of https://github.com/magento-hackathon/UniversalGaConversionTracking
- [Atwix](http://www.atwix.com)
- [Interactiv4](http://www.interactiv4.com/)