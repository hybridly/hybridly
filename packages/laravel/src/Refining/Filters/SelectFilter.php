<?php

namespace Hybridly\Refining\Filters;

use BackedEnum;
use Closure;
use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Hybridly\Refining\Filters\Operator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class SelectFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected ?Closure $query = null;
    protected array $searchColumns = [];
    protected null|Closure|bool $isMultiple = false;
    protected false|Closure|int $preloadQueryBuilderOptionsUsing = false;
    protected ?Closure $formatSelectedOptionsLabelUsing = null;
    protected ?Closure $formatSelectedOptionLabelUsing = null;
    protected ?Closure $resolveBuilderOptionFromKeyUsing = null;
    protected null|Closure|string $selectedOptionsLabel = null;
    protected Closure|false $searchBuilderOptionsUsing = false;
    protected Closure|bool $preventProvidingOptions = false;
    protected ?Closure $formatOptionLabelUsing = null;
    protected ?Closure $resolveOptionKeyUsing = null;
    protected ?Closure $parseOptionUsing = null;
    protected ?string $enum = null;
    protected ?Builder $builder = null;
    protected ?Closure $resolveBuilderOptionsUsing = null;
    protected Closure|array $resolveOptionsUsing = [];
    protected ?string $relationshipName = null;
    protected string $relationshipTitleColumn = 'name';
    protected ?Closure $relationshipQuery = null;
    protected bool $hasEmptyRelationshipOption = false;
    protected Closure|string $emptyRelationshipOptionLabel = 'None';
    protected null|Closure|string $emptyLabel = null;
    protected null|Closure|string $noSearchResultLabel = null;

    /**
     * List of all available options.
     *
     * @param Model[]|UnitEnum[]|string[]|int[]
     */
    public array $availableOptions {
        get => $this->availableOptions ??= $this->resolveOptions();
    }

    /**
     * List of all selected options.
     *
     * @param Model[]|UnitEnum[]|string[]|int[]
     */
    public array $selectedOptions {
        get => $this->selectedOptions ??= $this->resolveSelectedOptions();
    }

    protected function setUp(): void
    {
        $this->type('select');

        $this->supportedOperators(fn () => match ($this->isMultiple()) {
            true => [
                Operator::IN,
                Operator::NOT_IN,
            ],
            false => [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
            ],
        });

        $this->defaultOperator(fn () => $this->isMultiple() ? Operator::IN : Operator::EQUALS);

        $this->resolveOptionKeyUsing(fn (Model|UnitEnum|string|int $option) => match (true) {
            $option instanceof BackedEnum => $option->value,
            $option instanceof UnitEnum => $option->name,
            $option instanceof Model => $option->getKey(),
            default => null,
        });

        $this->formatOptionLabelUsing(function (Model|UnitEnum|string|int $option) {
            if ($this->isRelationship() && $option instanceof Model) {
                return $option->getAttribute($this->relationshipTitleColumn);
            }

            if ($option instanceof Model && $this->searchColumns !== []) {
                return $option->getAttribute($this->searchColumns[0]);
            }

            if ($option instanceof BackedEnum) {
                return $option->name;
            }

            if ($option instanceof UnitEnum) {
                return $option->name;
            }

            return $option;
        });

        $this->formatSelectedOptionLabelUsing(function (Model|UnitEnum|string|int|null $option) {
            return $this->evaluate(
                value: $this->formatOptionLabelUsing,
                named: [
                    'option' => $option,
                ],
                typed: [
                    UnitEnum::class => $option,
                    Model::class => $option,
                ],
                positional: [
                    0 => $option,
                ],
            );
        });

        $this->formatSelectedOptionsLabelUsing(function (array $options) {
            if ($this->isMultiple() && count($options) > 1) {
                $label = $this->evaluate($this->selectedOptionsLabel) ?: ($this->alias ?: $this->name);

                return Str::plural($label, count: count($options), prependCount: true);
            }

            if (count($options) === 1) {
                return $this->evaluate(
                    value: $this->formatSelectedOptionLabelUsing,
                    named: [
                        'key' => array_key_first($options),
                        'option' => array_first($options),
                    ],
                    typed: [
                        UnitEnum::class => array_first($options),
                        Model::class => array_first($options),
                    ],
                    positional: [
                        0 => array_first($options),
                    ],
                );
            }

            return null;
        });

        $this->resolveBuilderOptionsUsing(function (Builder $builder) {
            if (! $this->isSearching() && ! $this->shouldPreload()) {
                return [];
            }

            if (! $this->isSearching() && $this->shouldPreload()) {
                $this->evaluate($this->preloadQueryBuilderOptionsUsing, [
                    'builder' => $builder,
                    'query' => $builder,
                ]);
            }

            if ($this->isSearching() && $this->isSearchable()) {
                $this->evaluate($this->searchBuilderOptionsUsing, [
                    'builder' => $builder,
                    'query' => $builder,
                    'search' => $this->filter?->search,
                ]);
            }

            if ($this->filter?->value) {
                $builder->where(
                    function (Builder $builder) {
                        $this->evaluate($this->resolveBuilderOptionFromKeyUsing, named: [
                            'builder' => $builder,
                            'query' => $builder,
                            'key' => $this->filter->value,
                        ]);
                    },
                    boolean: $this->isSearching() ? 'or' : 'and',
                );
            }

            return $builder->get();
        });

        $this->resolveBuilderOptionFromKeyUsing(function (Builder $builder, array|string|int $key) {
            if (is_array($key) && count($key) > 1) {
                $builder->whereIn($builder->getModel()->getKeyName(), array_values($key));
            } elseif (is_array($key) && count($key) === 1) {
                $builder->where($builder->getModel()->getKeyName(), array_first($key));
            } else {
                $builder->where($builder->getModel()->getKeyName(), $key);
            }

            return $builder->first();
        });

        $this->parseOptionUsing(function (int|string|array $value) {
            if (is_scalar($value) && $this->enum) {
                return $this->enum::tryFrom($value);
            }

            if ($this->isRelationship()) {
                $model = $this->getRelationshipModel();
                $builder = $model->query();

                if ($this->relationshipQuery) {
                    $this->evaluate($this->relationshipQuery, [
                        'query' => $builder,
                        'builder' => $builder,
                    ]);
                }

                return $this->evaluate(
                    $this->resolveBuilderOptionFromKeyUsing,
                    named: [
                        'key' => $value,
                        'builder' => $builder,
                        'query' => $builder,
                    ],
                    typed: [
                        Builder::class => $builder,
                        'string' => $value,
                        'int' => $value,
                        'array' => $value,
                    ],
                );
            }

            if ($this->builder) {
                $builder = $this->builder->clone();

                return $this->evaluate(
                    $this->resolveBuilderOptionFromKeyUsing,
                    named: [
                        'key' => $value,
                        'builder' => $builder,
                        'query' => $builder,
                    ],
                    typed: [
                        Builder::class => $builder,
                        'string' => $value,
                        'int' => $value,
                        'array' => $value,
                    ],
                );
            }

            return $value;
        });

        $this->query(function (Builder $builder, string $property, array $selectedOptions) {
            $this->ensureValuesAreAllowed($selectedOptions);

            if (count($selectedOptions) === 0) {
                if ($this->shouldApplyUnsatisfiableConstraint()) {
                    $this->applyUnsatisfiableConstraint($builder);
                }

                return;
            }

            if ($this->isRelationship()) {
                $property = $this->relationshipName . '.' . $this->getRelationshipModel()->getKeyName();
            }

            if ($this->isMultiple()) {
                return $this->applyRelationConstraint(
                    builder: $builder,
                    property: $property,
                    callback: fn (Builder $builder, string $column) => $builder->whereIn(
                        column: $this->qualifyColumn($builder, $column),
                        values: array_keys($selectedOptions),
                        boolean: $this->getQueryBoolean(),
                        not: match ($this->resolveOperator()) {
                            Operator::NOT_IN => true,
                            default => false,
                        },
                    ),
                );
            }

            return $this->applyRelationConstraint(
                builder: $builder,
                property: $property,
                callback: fn (Builder $builder, string $column) => $builder->where(
                    column: $this->qualifyColumn($builder, $column),
                    operator: match ($this->resolveOperator()) {
                        Operator::NOT_EQUALS => '!=',
                        default => '=',
                    },
                    value: array_key_first($selectedOptions),
                    boolean: $this->getQueryBoolean(),
                ),
            );
        });

        $this->appendMetadata(fn () => array_filter([
            'is_multiple' => $this->isMultiple(),
            'is_searchable' => $this->isSearchable(),
            'options' => $this->resolveOptionsMetadata(),
            'selected_options_label' => $this->getSelectedOptionsLabel(),
            'empty_label' => $this->getEmptyLabel(),
            'no_search_result_label' => $this->getNoSearchResultLabel(),
            'allows_empty_relationship_option' => $this->isRelationship() && $this->hasEmptyRelationshipOption
                ? true
                : null,
            'empty_relationship_option_label' => $this->isRelationship() && $this->hasEmptyRelationshipOption
                ? $this->evaluate($this->emptyRelationshipOptionLabel)
                : null,
        ]));
    }

    public static function make(string $property, ?string $alias = null, Closure|string|array $options = []): static
    {
        $static = resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);

        return $static->options($options);
    }

    public function apply(Builder $builder, QueryFilter $filter, string $property): void
    {
        $hasEmptyOption = data_get($filter->options, 'empty') === true || is_array($filter->value) && in_array(null, $filter->value, strict: true);

        // handles the empty option for relationships
        if ($this->isRelationship() && $this->hasEmptyRelationshipOption && $hasEmptyOption) {
            if (count($this->selectedOptions) === 0) {
                $builder->doesntHave($this->relationshipName, boolean: $this->getQueryBoolean());
                return;
            }

            $builder->where(function (Builder $query) {
                $query->whereDoesntHave($this->relationshipName);
                $query->orWhereHas($this->relationshipName, function (Builder $builder) {
                    if ($this->isMultiple()) {
                        $builder->whereIn($builder->getModel()->getKeyName(), array_keys($this->selectedOptions));
                    } else {
                        $builder->where($builder->getModel()->getKeyName(), array_key_first($this->selectedOptions));
                    }
                });
            }, boolean: $this->getQueryBoolean());

            return;
        }

        $this->evaluate(
            value: $this->query,
            named: [
                'key' => array_key_first($this->selectedOptions),
                'value' => array_first($this->selectedOptions),
                'values' => $this->selectedOptions,
                'options' => $this->selectedOptions,
                'selectedValues' => $this->selectedOptions,
                'selectedOptions' => $this->selectedOptions,
                'builder' => $builder,
                'query' => $builder,
                'property' => $property,
                'filter' => $filter,
                'operator' => $this->resolveOperator(),
            ],
            typed: [
                Operator::class => $this->resolveOperator(),
                Builder::class => $builder,
                'array' => $this->selectedOptions,
            ],
            positional: [
                1 => $this->selectedOptions,
            ],
        );
    }

    protected function resolveSelectedOptions(): array
    {
        return $this->parseQueryValue($this->filter->value);
    }

    public function resolveBuilderOptionFromKeyUsing(Closure $callback): self
    {
        $this->resolveBuilderOptionFromKeyUsing = $callback;

        return $this;
    }

    /**
     * Sets the label for the selected option(s) to be sent to the front-end. This is used when the front-end needs to display the selected option(s) label, for example in a pill.
     */
    public function selectedOptionsLabel(Closure|string $label): self
    {
        $this->selectedOptionsLabel = $label;

        return $this;
    }

    /**
     * Defines whether multiple options can be selected for this filter.
     */
    public function isMultiple(): bool
    {
        return $this->evaluate($this->isMultiple) === true;
    }

    /**
     * Defines a callback to format the selected options for front-end consumption. If there are multiple options, this callback will be called multiple times.
     */
    public function formatSelectedOptionsLabelUsing(Closure $callback): self
    {
        $this->formatSelectedOptionsLabelUsing = $callback;

        return $this;
    }

    /**
     * Defines a callback to format the selected option for front-end consumption.
     */
    public function formatSelectedOptionLabelUsing(Closure $callback): self
    {
        $this->formatSelectedOptionLabelUsing = $callback;

        return $this;
    }

    /**
     * Defines a callback or a limit to preload options when the options are provided as a query builder and there is no search term.
     */
    public function preload(int|Closure $callbackOrLimit): self
    {
        if (is_int($callbackOrLimit)) {
            $this->preloadQueryBuilderOptionsUsing = fn (Builder $builder) => $builder->limit($callbackOrLimit);
        } else {
            $this->preloadQueryBuilderOptionsUsing = $callbackOrLimit;
        }

        return $this;
    }

    /**
     * Defines a callback to parse the selected option for this filter. If there are multiple options, this callback will be called multiple times.
     */
    public function parseOptionUsing(Closure $parseOptionUsing): self
    {
        $this->parseOptionUsing = $parseOptionUsing;

        return $this;
    }

    /**
     * Prevents providing the options as metadata, so the front-end cannot access them.
     */
    public function withoutProvidingOptions(Closure|bool $preventProvidingOptions = true): self
    {
        $this->preventProvidingOptions = $preventProvidingOptions;

        return $this;
    }

    /**
     * Defines how a single option is formatted for front-end consumption.
     */
    public function formatOptionLabelUsing(Closure $callback): self
    {
        $this->formatOptionLabelUsing = $callback;

        return $this;
    }

    /**
     * Defines how to resolve the option id for a given option. This is used to determine the value that is sent to the server when an option is selected.
     */
    public function resolveOptionKeyUsing(Closure $callback): self
    {
        $this->resolveOptionKeyUsing = $callback;

        return $this;
    }

    /**
     * Defines the options for this filter.
     *
     * @example
     * ```php
     * // Enum FQCN
     * SelectFilter::make('category')
     *     ->options(Category::class);
     *
     * // Model or query
     * SelectFilter::make('users')
     *     ->options(User::query()->whereActive());
     *
     * // With plain array
     * SelectFilter::make('category')
     *     ->options([
     *         'draft' => 'Draft',
     *         'reviewing' => 'Reviewing',
     *         'published' => 'Published',
     *     ]);
     * ```
     */
    public function options(Closure|string|array|Builder $options): static
    {
        if (is_string($options) && is_a($options, UnitEnum::class, allow_string: true)) {
            $this->enum = $options;

            return $this;
        }

        if (is_string($options) && is_a($options, Model::class, allow_string: true)) {
            $options = $options::query();
        }

        if ($options instanceof Builder) {
            $this->builder = $options;

            return $this;
        }

        $this->resolveOptionsUsing = $options;

        return $this;
    }

    /**
     * Defines whether multiple choices can be selected.
     */
    public function multiple(Closure|bool $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    /**
     * Defines whether the select filter is searchable.
     */
    public function searchable(Closure|array|string $callbackOrColumns): static
    {
        if (is_string($callbackOrColumns)) {
            $this->searchColumns = [$callbackOrColumns];
            $callbackOrColumns = $this->searchColumns;
        }

        if (is_array($callbackOrColumns)) {
            $columns = $callbackOrColumns;
            $callbackOrColumns = fn (Builder $builder) => $builder
                ->limit(10)
                ->where(function (Builder $builder) use ($columns) {
                    foreach ($columns as $column) {
                        $builder->orWhereLike($column, "%{$this->filter->search}%");
                    }
                });
        }

        $this->searchBuilderOptionsUsing = $callbackOrColumns;

        return $this;
    }

    /**
     * Defines a callback to resolve the options when the options are provided as a query builder. The callback receives the query builder as an argument and should return an array of options of a reasonable size.
     */
    public function resolveBuilderOptionsUsing(Closure $callback): static
    {
        $this->resolveBuilderOptionsUsing = $callback;

        return $this;
    }

    /**
     * Configures this filter to use a relationship.
     *
     * @param string $relation The name of the relationship
     * @param string $column The column to use for display (default: 'name')
     * @param Closure|null $query Optional query modifier callback
     * @param bool $hasEmptyOption Whether to include an empty option (default: false)
     *
     * @example
     * ```php
     * // Basic usage
     * SelectFilter::make('author')
     *     ->relationship('author', 'name');
     *
     * // With query modifier (e.g., include trashed)
     * SelectFilter::make('author')
     *     ->relationship('author', 'name', fn (Builder $query) => $query->withTrashed());
     *
     * // Multiple selection
     * SelectFilter::make('categories')
     *     ->relationship('categories', 'name')
     *     ->multiple();
     *
     * // With empty option
     * SelectFilter::make('author')
     *     ->relationship('author', 'name', hasEmptyOption: true)
     *     ->emptyRelationshipOptionLabel('No author');
     * ```
     */
    public function relationship(string $relation, string $column = 'name', ?Closure $query = null, bool $hasEmptyOption = false): static
    {
        $this->relationshipName = $relation;
        $this->relationshipTitleColumn = $column;
        $this->relationshipQuery = $query;
        $this->hasEmptyRelationshipOption = $hasEmptyOption;

        $this->searchable($this->relationshipTitleColumn);

        return $this;
    }

    /**
     * Checks if this filter is configured to use a relationship.
     */
    public function isRelationship(): bool
    {
        return $this->relationshipName !== null;
    }

    /**
     * Checks if this filter is searchable.
     */
    public function isSearchable(): bool
    {
        return $this->searchBuilderOptionsUsing !== false;
    }

    /**
     * Checks if the filter is currently being searched.
     */
    public function isSearching(): bool
    {
        return $this->filter?->search ? true : false;
    }

    /**
     * Checks if options should be preloaded when using a query builder as options source and there is no search term.
     */
    public function shouldPreload(): bool
    {
        return $this->preloadQueryBuilderOptionsUsing !== false;
    }

    /**
     * Sets the label for the empty relationship option.
     */
    public function emptyRelationshipOptionLabel(Closure|string $label): static
    {
        $this->emptyRelationshipOptionLabel = $label;

        return $this;
    }

    /**
     * Sets the label to display when there are no options available.
     */
    public function emptyLabel(Closure|string $label): static
    {
        $this->emptyLabel = $label;

        return $this;
    }

    /**
     * Gets the label to display when there are no options available.
     */
    public function getEmptyLabel(): ?string
    {
        return $this->evaluate($this->emptyLabel);
    }

    /**
     * Sets the label to display when a search returns no results.
     */
    public function noSearchResultLabel(Closure|string $label): static
    {
        $this->noSearchResultLabel = $label;

        return $this;
    }

    /**
     * Gets the label to display when a search returns no results.
     */
    public function getNoSearchResultLabel(): ?string
    {
        return $this->evaluate($this->noSearchResultLabel);
    }

    /**
     * Gets the relationship model from the parent builder.
     */
    protected function getRelationshipModel(): Model
    {
        $parentBuilder = $this->refine->getBuilderInstance();
        $relation = $parentBuilder->getModel()->{$this->relationshipName}();

        return $relation->getRelated();
    }

    public function query(Closure $callback): self
    {
        $this->query = $callback;

        return $this;
    }

    protected function ensureValuesAreAllowed(mixed $values): bool
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        if ($this->enum) {
            $diff = array_diff(
                array_map(
                    callback: fn ($value) => match (true) {
                        $value instanceof BackedEnum => $value->value,
                        $value instanceof UnitEnum => $value->name,
                        is_scalar($value) => $value,
                        default => null,
                    },
                    array: $values,
                ),
                array_map(
                    callback: fn ($option) => match (true) {
                        $option instanceof BackedEnum => $option->value,
                        $option instanceof UnitEnum => $option->name,
                        default => null,
                    },
                    array: $this->enum::cases(),
                ),
            );

            return $diff === [];
        }

        return true;
    }

    protected function getSelectedOptionsLabel(): null|array|string
    {
        if (! $this->filter) {
            return null;
        }

        if ($this->filter->value === null && data_get($this->filter->options, 'empty') === true) {
            return $this->evaluate($this->emptyRelationshipOptionLabel);
        }

        return $this->evaluate($this->formatSelectedOptionsLabelUsing, [
            'options' => $this->selectedOptions,
            'selectedOptions' => $this->selectedOptions,
            'availableOptions' => $this->availableOptions,
            'filter' => $this->filter,
            'value' => $this->filter->value,
        ]);
    }

    protected function parseQueryValue(null|int|string|array $values): array
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        return collect($values)
            ->mapWithKeys(function (null|int|string|Model|UnitEnum $key) {
                if ($key === null) {
                    return [];
                }

                $value = $this->evaluate(
                    value: $this->parseOptionUsing,
                    named: [
                        'key' => $key,
                        'value' => $key,
                        'options' => $this->availableOptions,
                        'availableOptions' => $this->availableOptions,
                    ],
                );

                return [$key => $value];
            })
            ->filter(fn ($value) => $value !== null)
            ->all();
    }

    protected function getOptionKey(int|string|Model|UnitEnum $option): string|int|null
    {
        return $this->evaluate(
            value: $this->resolveOptionKeyUsing,
            named: [
                'option' => $option,
            ],
            typed: [
                'array' => $this->availableOptions,
                Collection::class => collect($this->availableOptions),
                UnitEnum::class => $this->enum ? $option : null,
                Model::class => $option,
            ],
            positional: [
                0 => $option,
            ],
        );
    }

    protected function getOptionLabel(int|string|Model|UnitEnum $option): array|string|int|null
    {
        return $this->evaluate(
            value: $this->formatOptionLabelUsing,
            named: [
                'option' => $option,
            ],
            typed: [
                'array' => $this->availableOptions,
                Collection::class => collect($this->availableOptions),
                UnitEnum::class => $this->enum ? $option : null,
                Model::class => $option,
            ],
            positional: [
                0 => $option,
            ],
        );
    }

    protected function resolveOptionsMetadata(): ?array
    {
        if ($this->evaluate($this->preventProvidingOptions) === true) {
            return null;
        }

        return collect($this->availableOptions)
            ->mapWithKeys(function (int|string|Model|UnitEnum $option, int|string $i) {
                $key = $this->getOptionKey($option);
                $label = $this->getOptionLabel($option);

                return [$key ?? $i => $label];
            })
            ->all();
    }

    protected function resolveOptions(): array
    {
        if ($this->enum) {
            return $this->enum::cases();
        }

        $options = $this->evaluate($this->resolveOptionsUsing);
        $builder = $this->builder;

        if ($this->isRelationship()) {
            $model = $this->getRelationshipModel();
            $builder = $model->query();

            if ($this->relationshipQuery) {
                $this->evaluate($this->relationshipQuery, [
                    'query' => $builder,
                    'builder' => $builder,
                ]);
            }
        }

        if ($builder) {
            $options = $this->evaluate(
                value: $this->resolveBuilderOptionsUsing,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                ],
                typed: [
                    Builder::class => $builder,
                ],
            );
        }

        if ($options instanceof Collection) {
            $options = $options->all();
        }

        if (! is_array($options)) {
            return [];
        }

        return $options;
    }

    /**
     * Determines whether an explicit but unresolvable selection should yield no results.
     *
     * This guards inclusion semantics (`equals` / `in`) from silently becoming a no-op
     * when provided values cannot be resolved to actual options (e.g. invalid enum,
     * missing id, soft-deleted related model not included by the relationship query).
     *
     * In those cases, we apply an impossible constraint so the query returns zero rows,
     * which is consistent with the user's explicit inclusion intent.
     */
    protected function shouldApplyUnsatisfiableConstraint(): bool
    {
        $value = $this->filter?->value;

        if ($value === null) {
            return false;
        }

        if (is_array($value) && array_filter($value, fn (mixed $item) => $item !== null) === []) {
            return false;
        }

        return match ($this->resolveOperator()) {
            Operator::EQUALS, Operator::IN => true,
            default => false,
        };
    }

    protected function applyUnsatisfiableConstraint(Builder $builder): void
    {
        $builder->whereRaw('0 = 1', boolean: $this->getQueryBoolean());
    }
}
