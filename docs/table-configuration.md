# Table Configuration

In the table configuration you define all the columns of the table

## Table Options

All Options are as constants in `Table` class.

- `title`: String|Null
- `searchable`: Boolean, default: `false`
- `sortable`: Boolean, default: `true`
- `attr`:
- `table_attr`: 
- `default_limit` : Int, dafault: `25`
- `limit_choices`:  Int, possible values: [`10`, `25`, `50`, `100`, `200`]
- `table_box_template`: String, box template, default: `@araiseTable/table.html.twig`
- `table_template`: String, table template, default:  `@araiseTable/tableOnly.html.twig`
- `default_sort`:
- `content_visibility`: Array with option, default:
- - `content_show_pagination`: Boolean, default: `true`
- - `content_show_result_label`: Boolean, default: `true`
- - `content_show_header`: Boolean, default: `true`
- - `content_show_entry_dropdown`: Boolean, default: `true`
- - `content_show_pagination_if_page_total_less_than_limit`: Boolean, default: `true`
- `sub_table_collapsed`: Boolean or callable, default: `true`


### Collapse Sub-Tables
By default, sub-tables will be rendered collapsed. 
This can be changed by setting the `sub_table_collapsed` option to either `false` or pass a callable that returns a boolean.

```php
$table->setOption(Table::OPT_SUB_TABLE_COLLAPSED, false);
```

Note that if you pass a function, you will get the current row as an argument.
You can use this to only expand certain rows:

```php
// Expand only users with an email
$table->setOption(Table::OPT_SUB_TABLE_COLLAPSED, function(array|object $row) {
    if(!$row instanceof User) {
        return true;
    }
    if($row->getEmail() !== null) {
        return false;
    }
    return true;
});
```


## Column Options

Example
```php
$table
    ->addColumn('firstname')
    ->addColumn('lastname')
    ->addColumn('birthday', null, [
        'sortable' => false,
        'formatter' => UserBirthdayFormatter::class,
    ])
;
```

For the columns you have a few options to tweak how they behave:

All Options are as constants in `Column` class.

- `label`: String, column name
- `callable`: Function, callable to get the data
- `accessor_path`: String, defaults to the acronym
- `formatter`: [Formatter](formatter.md)
- `sort_expression`: String, example: `'trainerGroup.name'`

## Footer Columns
In this example we would like to add a count of the content at the end of our table.
We can to this by adding a footer column like so:

```php
$data= [
    [
        'id' => 1,
    ],
    [
        'id' => 2,
    ]
];

$table
    ->setFooterData(['count' => count($data)])
    ->addFooterColumn('totalLabel', null, [
        Column::OPT_CALLABLE => fn (array $content) => 'Total',
    ])
    ->addFooterColumn('count', null, [
        Column::OPT_CALLABLE => fn(array $content) => $content['count'],
    ])
;
```

Note that this is only a simple example. The data could easily be replaced with an array of entities for example.

## Action Columns

Action Columns are here to link to other pages (f.ex. link to edit or view).
This column has a special template to render the links.

### Add A Table Action
You can add your own table actions like this:
```php
$table->addAction('test', [
    Action::OPT_LABEL => 'Test Button',
    Action::OPT_ROUTE => self::getRoute(Page::SHOW),
    Action::OPT_ROUTE_PARAMETERS => fn ($row) => [
        'id' => $row->getId(),
    ],
]);
```
As per default, the `addAction()`-method will use the `Action` class for your configuration.

To change which rows can use an action, try using the `TableAction` class and its `visibility` option instead:
```php
$table->addAction('test', [
    Action::OPT_LABEL => 'Test Button',
    Action::OPT_ROUTE => self::getRoute(Page::SHOW),
    Action::OPT_ROUTE_PARAMETERS => fn ($row) => [
        'id' => $row->getId(),
    ],
    TableAction::OPT_VISIBILITY => fn($row) => $row->getId() % 2 === 0,
], TableAction::class);
```
You can also simply set the `visibility` to a boolean instead of giving it a function:
```php
TableAction::OPT_VISIBILITY => false
```

### Options
Take a look at the constants on the `Action` or the `TableAction` class.
You can use all of these to further configure your action.

## Filters

Our tables can be filtered very easily. Most of it works automatically. See chapter [Filters](filters.md) for more info. 
