[![Build Status](https://travis-ci.org/ohader/data.svg?branch=master)](https://travis-ci.org/ohader/data)

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

* ~~[Allow to define multiple DataHandler commands for one element](https://review.typo3.org/#/q/I473ea0de3789d77cb913ad64a26a666ab73c2a52,n,z)~~ *(merged)*
* [Allow to define multiple inlineLocalizeSynchronize commands](https://review.typo3.org/#/q/Ic7e1293a44047bfd69017e240dd8563a1dffa423,n,z)

### Related

* [Resolve FlexForm fields in version dependency resolver](https://review.typo3.org/#/c/44202/ "Forge #70921")

## Open Tasks

* integrate handling for FlexForm data in dependency resolving
* switch to TCA meta-models for resolving possible dependencies instead of relying on the reference index
