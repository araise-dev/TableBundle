<?php

declare(strict_types=1);

namespace araise\TableBundle\Table;

use araise\CoreBundle\Action\Action;
use araise\CoreBundle\Manager\FormatterManager;
use araise\TableBundle\DataLoader\DataLoaderInterface;
use araise\TableBundle\Event\DataLoadEvent;
use araise\TableBundle\Extension\ExtensionInterface;
use araise\TableBundle\Extension\FilterExtension;
use araise\TableBundle\Extension\PaginationExtension;
use araise\TableBundle\Extension\SearchExtension;
use araise\TableBundle\Extension\SortExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Table
{
    public const OPT_TITLE = 'title';

    public const OPT_PRIMARY_LINK = 'primary_link';

    public const OPT_ATTRIBUTES = 'attributes';

    public const OPT_SEARCHABLE = 'searchable';

    public const OPT_SORTABLE = 'sortable';

    public const OPT_DEFAULT_SORT = 'default_sort';

    public const OPT_DEFAULT_LIMIT = 'default_limit';

    public const OPT_LIMIT_CHOICES = 'limit_choices';

    public const OPT_THEME = 'theme';

    public const OPT_DEFINITION = 'definition';

    public const OPT_DATALOADER_OPTIONS = 'dataloader_options';

    public const OPT_DATA_LOADER = 'data_loader';

    public const OPT_CONTENT_VISIBILITY = 'content_visibility';

    public const OPT_CONTENT_SHOW_PAGINATION = 'content_show_pagination';

    public const OPT_CONTENT_SHOW_RESULT_LABEL = 'content_show_result_label';

    public const OPT_CONTENT_SHOW_HEADER = 'content_show_header';

    public const OPT_CONTENT_SHOW_ENTRY_DROPDOWN = 'content_show_entry_dropdown';

    public const OPT_SUB_TABLE_LOADER = 'sub_table_loader';

    protected array $columns = [];

    protected array $actions = [];

    protected array $batchActions = [];

    protected \Traversable $rows;

    protected bool $loaded = false;

    protected ?Table $parent = null;

    public function __construct(
        protected string $identifier,
        protected array $options,
        protected EventDispatcherInterface $eventDispatcher,
        protected array $extensions,
        protected FormatterManager $formatterManager
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($this->options);
        foreach ($this->extensions as $extension) {
            $extension->setTable($this);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPT_TITLE => null,
            self::OPT_PRIMARY_LINK => null,
            self::OPT_ATTRIBUTES => [],
            self::OPT_SEARCHABLE => false,
            self::OPT_SORTABLE => true,
            self::OPT_DEFAULT_SORT => [],
            self::OPT_DEFAULT_LIMIT => 25,
            self::OPT_LIMIT_CHOICES => [25, 50, 100, 200, 500],
            self::OPT_CONTENT_VISIBILITY => function (OptionsResolver $contentResolver) {
                $contentResolver
                    ->setDefaults([
                        self::OPT_CONTENT_SHOW_PAGINATION => true,
                    ])
                    ->setDefaults([
                        self::OPT_CONTENT_SHOW_RESULT_LABEL => true,
                    ])
                    ->setDefaults([
                        self::OPT_CONTENT_SHOW_HEADER => true,
                    ])
                    ->setDefaults([
                        self::OPT_CONTENT_SHOW_ENTRY_DROPDOWN => true,
                    ]);
            },
            self::OPT_THEME => '@araiseTable/tailwind_2_layout.html.twig',
            self::OPT_DEFINITION => null,
            self::OPT_DATALOADER_OPTIONS => [],
            self::OPT_SUB_TABLE_LOADER => null,
        ]);

        $resolver->setAllowedTypes(self::OPT_TITLE, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPT_PRIMARY_LINK, ['null', 'callable']);
        $resolver->setAllowedTypes(self::OPT_ATTRIBUTES, ['array']);
        $resolver->setAllowedTypes(self::OPT_SEARCHABLE, ['boolean']);

        $resolver->setAllowedTypes(self::OPT_DEFAULT_LIMIT, ['integer']);

        $resolver->setAllowedTypes(self::OPT_THEME, ['string']);
        $resolver->setAllowedTypes(self::OPT_LIMIT_CHOICES, ['array']);
        $resolver->setAllowedTypes(self::OPT_DEFAULT_SORT, ['array']);
        $resolver->setAllowedTypes(self::OPT_DEFINITION, ['null', 'object']);

        $resolver->setAllowedTypes(self::OPT_SUB_TABLE_LOADER, ['null', 'callable']);
        $resolver->setRequired(self::OPT_DATA_LOADER);
        $resolver->setAllowedTypes(self::OPT_DATA_LOADER, allowedTypes: DataLoaderInterface::class);
    }

    public function getSubTables(object|array $row): array
    {
        if ($this->getOption(self::OPT_SUB_TABLE_LOADER) === null) {
            return [];
        }

        $subTables = ($this->getOption(self::OPT_SUB_TABLE_LOADER))($row);
        if (!$subTables) {
            return [];
        }

        if (!is_array($subTables)) {
            $subTables = [$subTables];
        }

        foreach ($subTables as $table) {
            $table->setParent($this);
        }

        return $subTables;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getOption(string $key, ...$args)
    {
        return $this->options[$key] ?? null;
    }

    public function getPrimaryLink(object|array $row)
    {
        return is_callable($this->options[self::OPT_PRIMARY_LINK]) ? $this->options[self::OPT_PRIMARY_LINK]($row) : null;
    }

    public function setOption(string $key, $value): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options[$key] = $value;
        $this->options = $resolver->resolve($this->options);

        if ($key === self::OPT_DEFAULT_LIMIT && $this->hasExtension(PaginationExtension::class)) {
            $this->getPaginationExtension()->setLimit($value);
        }

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        usort($this->columns, fn (Column $a, Column $b) => $b->getOption(Column::OPT_PRIORITY) <=> $a->getOption(Column::OPT_PRIORITY));

        return $this->columns;
    }

    public function addColumn(string $acronym, $type = null, array $options = [], ?int $position = null): static
    {
        if ($type === null) {
            $type = Column::class;
        }

        if ($this->options[self::OPT_DEFINITION]) {
            if (!isset($options[Column::OPT_LABEL])) {
                $options[Column::OPT_LABEL] = sprintf('wwd.%s.property.%s', $this->options[self::OPT_DEFINITION]->getEntityAlias(), $acronym);
            }
        }

        // set link_the_column_content on first column if not set
        if (!isset($options[Column::OPT_LINK_THE_COLUMN_CONTENT]) && count($this->columns) === 0) {
            $options[Column::OPT_LINK_THE_COLUMN_CONTENT] = true;
        }

        $column = new $type($this, $acronym, $options);

        if ($column instanceof FormattableColumnInterface) {
            $column->setFormatterManager($this->formatterManager);
        }

        if ($position === null) {
            $this->columns[$acronym] = $column;
        } else {
            $this->insertColumnAtPosition($acronym, $column, $position);
        }

        return $this;
    }

    public function removeColumn($acronym): static
    {
        unset($this->columns[$acronym]);

        return $this;
    }

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->getInternalActions($this->actions);
    }

    public function getAction(string $acronym): ?\araise\CoreBundle\Action\Action
    {
        if (isset($this->actions[$acronym])) {
            return $this->actions[$acronym];
        }

        return null;
    }

    /**
     * @return Action[]
     */
    public function getBatchActions(): array
    {
        return $this->getInternalActions($this->batchActions);
    }

    public function addAction(string $acronym, array $options = [], $type = Action::class): static
    {
        $this->actions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    public function removeAction(string $acronym): static
    {
        if (isset($this->actions[$acronym])) {
            unset($this->actions[$acronym]);
        }

        return $this;
    }

    public function getBatchAction(string $acronym): ?\araise\CoreBundle\Action\Action
    {
        if (isset($this->batchActions[$acronym])) {
            return $this->batchActions[$acronym];
        }

        return null;
    }

    public function addBatchAction(string $acronym, array $options = [], string $type = Action::class): static
    {
        if (! isset($options['voter_attribute'])) {
            $options['voter_attribute'] = 'batch_action';
        }
        $this->batchActions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    public function removeBatchAction(string $acronym): static
    {
        if (isset($this->batchActions[$acronym])) {
            unset($this->batchActions[$acronym]);
        }

        return $this;
    }

    public function getRows(): \Traversable
    {
        $this->loadData();

        return $this->rows;
    }

    /**
     * @deprecated  use twig function araise_table_render()
     */
    public function render(): string
    {
        throw new \Exception('\araise\TableBundle\Table\Table::render is deprecated, use twig function araise_table_render()');
    }

    public function getExtension(string $extension): ExtensionInterface
    {
        if (! $this->hasExtension($extension)) {
            throw new \InvalidArgumentException(sprintf('Extension %s is not enabled. Please configure it first.', $extension));
        }

        return $this->extensions[$extension]->setTable($this);
    }

    public function removeExtension(string $extension): void
    {
        unset($this->extensions[$extension]);
    }

    public function getSearchExtension(): ?SearchExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(SearchExtension::class)
            ? $this->getExtension(SearchExtension::class)
            : null;
    }

    public function getPaginationExtension(): ?PaginationExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(PaginationExtension::class)
            ? $this->getExtension(PaginationExtension::class)
            : null;
    }

    public function getFilterExtension(): ?FilterExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(FilterExtension::class)
            ? $this->getExtension(FilterExtension::class)
            : null;
    }

    public function getSortExtension(): ?SortExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(SortExtension::class)
            ? $this->getExtension(SortExtension::class)
            : null;
    }

    public function hasExtension(string $extension): bool
    {
        return \array_key_exists($extension, $this->extensions);
    }

    public function getDataLoader(): DataLoaderInterface
    {
        return $this->options[self::OPT_DATA_LOADER];
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function hasBatchActions(): bool
    {
        return $this->parent === null
            && count($this->getBatchActions()) > 0
        ;
    }

    public function getColspan(int $extra = 0): int
    {
        return ($this->hasBatchActions() ? 1 : 0)
            + count($this->getColumns())
            + $extra
        ;
    }

    protected function loadData(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::PRE_LOAD);
        $this->rows = $this->options[self::OPT_DATA_LOADER]->getResults();
        $this->loaded = true;
        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::POST_LOAD);
    }

    protected function insertColumnAtPosition($key, $value, $position)
    {
        $newArray = [];
        $added = false;
        $i = 0;
        foreach ($this->columns as $elementsAcronym => $elementsElement) {
            if ($position === $i) {
                $newArray[$key] = $value;
                $added = true;
            }
            $newArray[$elementsAcronym] = $elementsElement;
            ++$i;
        }
        if (! $added) {
            $newArray[$key] = $value;
        }
        $this->columns = $newArray;
    }

    /**
     * @return Action[]
     */
    private function getInternalActions(array $actions): array
    {
        uasort(
            $actions,
            static fn (Action $a, Action $b) => $a->getOption(Action::OPT_PRIORITY) <=> $b->getOption(Action::OPT_PRIORITY)
        );

        return $actions;
    }
}
