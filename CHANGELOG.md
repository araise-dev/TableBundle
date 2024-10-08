# CHANGELOG

## v1.2.0
 - Removed symfony ^5.4 support
 - Added Table option `OPT_SUB_TABLE_COLLAPSED`. This will collapse the sub table by default, you can also pass a callable to determine if the sub table should be collapsed or not 
 - Added footer columns to the table. This can be used to display totals or other information
 - Fixed a bug where the table count would be of when using group by in the default query builder
 - Date filters now use the `datetime_controller.js` provided by the core-bundle
 - UX improvements

## v1.0.7
 - More documentation and better styling of the documentation
 - Added a new optional parameter to the `FilterTypeInterface::getValueField()` method to allow for more complex value fields.
 - Introduced `FilterOperatorDto` to allow for more complex filter operators
 - Improved Relation Content styling
 - Predefined filters will show up in the filter dropdown

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
