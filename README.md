# TYPO3 CMS Data Handling Extension

Several additions for handling data in TYPO3 CMS

## Functional Tests

```
./bin/phpunit -c typo3conf/ext/data/Build/FunctionalTests.xml
```

## Aspects

* restructure and reorder process flow concerning parent-child dependencies
* validate, correct and reduce localization instructions

## Patches

### Required

* [Enhance inlineLocalizeSynchronize command handling](https://review.typo3.org/#/c/44232/ "Forge #xxxxx")

### Related

* [Resolve FlexForm fields in version dependency resolver](https://review.typo3.org/#/c/44202/ "Forge #70921")

## Open Tasks

* integrate handling for FlexForm data in dependency resolving
* switch to TCA meta-models for resolving possible dependencies instead of relying on the reference index
