# CHANGELOG

## v1.0.6
 - Removed dependency to `coduo/php-to-string` 
 - Added new bundle configuration `enable_turbo` (default `false`)
     - **BREAKING CHANGE**: Set `enable_turbo` to `true` to get back the old behavior.
 - Added `OPT_VISIBILITY` to Table Actions
 - `CRITERIA_CONTAINS` is now the default Criteria for `TextFilterType`
 - Batch Actions can return a links to open in a new tab or window
 - More documentation and better styling of the documentation
 - Improved styling generally

## v1.0.2
 - fix only add accordion toggle trigger if in subTables

## v1.0.1
 - English translation
 - Improved & Documented `ArrayDataLoader`
 - Filters are loaded over Dependency Injection
 - Batch Actions are defined on the table
 - Lots of styling improvements
