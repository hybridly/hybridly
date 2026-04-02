<?php

namespace Hybridly\Refining\Filters;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Hybridly\Refining\Concerns\SupportsRelationConstraints;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DateFilter extends BaseFilter
{
    use SupportsRelationConstraints;

    protected bool $isTimeframe = false;
    protected ?string $startColumn = null;
    protected ?string $endColumn = null;
    protected array $suggestions = [];
    protected ?\Closure $query = null;
    protected string|\Closure|null $title = null;
    protected string|\Closure|null $description = null;
    protected ?\Closure $formatDateUsing = null;

    protected function setUp(): void
    {
        $this->type('date');

        $this->supportedOperators(fn () => $this->isTimeframe
            ? [
                Operator::BETWEEN,
                Operator::NOT_BETWEEN,
            ] : [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
                Operator::AFTER,
                Operator::BEFORE,
                Operator::IS_NULL,
                Operator::IS_NOT_NULL,
            ]);

        $this->defaultOperator(fn () => $this->isTimeframe ? Operator::BETWEEN : Operator::EQUALS);

        $this->query(function (Builder $builder, ?CarbonInterface $start, ?CarbonInterface $end, string $property) {
            if ($this->isTimeframe) {
                $this->applyDefaultTimeframeQuery($builder, $start, $end);
            } else {
                $this->applyDefaultSingleDateQuery($builder, $start, $property);
            }
        });

        $this->formatDateUsing(fn (CarbonInterface $date) => $date->toIso8601String());

        $this->appendMetadata(fn () => array_filter([
            'is_timeframe' => $this->isTimeframe,
            'start_column' => $this->startColumn,
            'end_column' => $this->endColumn,
            'suggestions' => $this->suggestions !== [] ? $this->getFormattedSuggestions() : null,
            'current_value_label' => $this->getCurrentValueLabel(),
            'title' => $this->evaluate($this->title),
            'description' => $this->evaluate($this->description),
        ]));
    }

    public static function make(string $property, ?string $alias = null): static
    {
        return resolve(static::class, [
            'property' => $property,
            'alias' => $alias,
        ]);
    }

    /**
     * Configure this filter as a timeframe filter with start and end columns.
     */
    public function timeframe(string $start, string $end): static
    {
        $this->isTimeframe = true;
        $this->startColumn = $start;
        $this->endColumn = $end;

        return $this;
    }

    /**
     * Add suggestions to the filter.
     *
     * @param  array<TimeSuggestion|TimeframeSuggestion>  $suggestions
     */
    public function suggest(array $suggestions): static
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    /**
     * Set a custom query callback for this filter.
     */
    public function query(\Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    /**
     * Set the title for this filter.
     */
    public function title(string|\Closure $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the description for this filter.
     */
    public function description(string|\Closure $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set a custom date formatter for suggestions and values.
     */
    public function formatDateUsing(\Closure $callback): static
    {
        $this->formatDateUsing = $callback;

        return $this;
    }

    public function apply(Builder $builder, QueryFilter $filter, string $property): void
    {
        if ($this->isTimeframe) {
            $dates = $this->getTimeframeDatesFromValue($filter->value);

            if (! $dates) {
                return;
            }

            ['start' => $startDate, 'end' => $endDate] = $dates;

            $this->evaluate(
                value: $this->query,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                    'start' => $startDate,
                    'end' => $endDate,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'property' => $property,
                    'filter' => $filter,
                    'operator' => $this->resolveOperator(),
                ],
                typed: [
                    Builder::class => $builder,
                    Carbon::class => $startDate,
                ],
                positional: [
                    0 => $builder,
                    1 => $startDate,
                    2 => $endDate,
                ],
            );
        } else {
            $date = $this->parseDate($filter->value);

            $this->evaluate(
                value: $this->query,
                named: [
                    'builder' => $builder,
                    'query' => $builder,
                    'date' => $date,
                    'start' => $date,
                    'end' => null,
                    'property' => $property,
                    'filter' => $filter,
                    'operator' => $this->resolveOperator(),
                ],
                typed: [
                    Builder::class => $builder,
                    Carbon::class => $date,
                ],
                positional: [
                    0 => $builder,
                    1 => $date,
                ],
            );
        }
    }

    protected function applyDefaultSingleDateQuery(Builder $builder, CarbonInterface $date, string $property): void
    {
        $this->applyRelationConstraint(
            builder: $builder,
            property: $property,
            callback: function (Builder $builder, string $column, bool $isRelation) use ($date) {
                $qualifiedColumn = $this->qualifyColumn($builder, $column);
                $boolean = $isRelation ? 'and' : $this->getQueryBoolean();

                match ($this->resolveOperator()) {
                    Operator::EQUALS => $builder->whereDate($qualifiedColumn, '=', $date, $boolean),
                    Operator::NOT_EQUALS => $builder->whereDate($qualifiedColumn, '!=', $date, $boolean),
                    Operator::AFTER => $builder->whereDate($qualifiedColumn, '>', $date, $boolean),
                    Operator::BEFORE => $builder->whereDate($qualifiedColumn, '<', $date, $boolean),
                    Operator::IS_NULL => $builder->whereNull($qualifiedColumn, $boolean),
                    Operator::IS_NOT_NULL => $builder->whereNotNull($qualifiedColumn, $boolean),
                };
            },
        );
    }

    protected function applyDefaultTimeframeQuery(Builder $builder, CarbonInterface $startDate, CarbonInterface $endDate): void
    {
        $boolean = $this->getQueryBoolean();

        // Check if both columns have the same relation prefix
        $startRelation = str_contains($this->startColumn, '.')
            ? Str::beforeLast($this->startColumn, '.')
            : null;

        $endRelation = str_contains($this->endColumn, '.')
            ? Str::beforeLast($this->endColumn, '.')
            : null;

        // If both columns are on the same relation, apply constraint once
        if ($startRelation && $startRelation === $endRelation) {
            $startColumnName = substr($this->startColumn, strrpos($this->startColumn, '.') + 1);
            $endColumnName = substr($this->endColumn, strrpos($this->endColumn, '.') + 1);

            $builder->has(
                relation: $startRelation,
                boolean: $boolean,
                callback: function (Builder $builder) use ($startColumnName, $endColumnName, $startDate, $endDate) {
                    $startQualified = $this->qualifyColumn($builder, $startColumnName);
                    $endQualified = $this->qualifyColumn($builder, $endColumnName);

                    match ($this->resolveOperator()) {
                        Operator::BETWEEN => $builder->where(function (Builder $query) use ($startQualified, $endQualified, $startDate, $endDate) {
                            $query
                                ->whereDate($startQualified, '>=', $startDate)
                                ->whereDate($endQualified, '<=', $endDate);
                        }),
                        Operator::NOT_BETWEEN => $builder->where(function (Builder $query) use ($startQualified, $endQualified, $startDate, $endDate) {
                            $query
                                ->whereDate($startQualified, '<', $startDate)
                                ->orWhereDate($endQualified, '>', $endDate);
                        }),
                    };
                },
            );

            return;
        }

        // No relations or different relations - apply directly
        $startQualified = $this->qualifyColumn($builder, $this->startColumn);
        $endQualified = $this->qualifyColumn($builder, $this->endColumn);

        match ($this->resolveOperator()) {
            Operator::BETWEEN => $builder->where(function (Builder $query) use ($startQualified, $endQualified, $startDate, $endDate) {
                $query->whereDate($startQualified, '>=', $startDate);
                $query->whereDate($endQualified, '<=', $endDate);
            }, boolean: $boolean),
            Operator::NOT_BETWEEN => $builder->where(function (Builder $query) use ($startQualified, $endQualified, $startDate, $endDate) {
                $query->whereDate($startQualified, '<', $startDate);
                $query->orWhereDate($endQualified, '>', $endDate);
            }, boolean: $boolean),
        };
    }

    protected function parseDate(mixed $value): CarbonInterface
    {
        return CarbonImmutable::parse($value);
    }

    /**
     * Parse and validate timeframe dates from a value.
     *
     * @return array{start: CarbonInterface, end: CarbonInterface}|null
     */
    protected function getTimeframeDatesFromValue(mixed $value): ?array
    {
        if (! \is_array($value) || ! isset($value['start'], $value['end'])) {
            return null;
        }

        return [
            'start' => $this->parseDate($value['start']),
            'end' => $this->parseDate($value['end']),
        ];
    }

    protected function getCurrentValueLabel(): ?string
    {
        if (! $this->filter?->value) {
            return null;
        }

        if ($this->isTimeframe) {
            $dates = $this->getTimeframeDatesFromValue($this->filter->value);

            if (! $dates) {
                return null;
            }

            ['start' => $startDate, 'end' => $endDate] = $dates;

            foreach ($this->suggestions as $suggestion) {
                if (! ($suggestion instanceof TimeframeSuggestion)) {
                    continue;
                }

                if ($suggestion->start->is($startDate) && $suggestion->end->is($endDate)) {
                    return $suggestion->label;
                }
            }

            return $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y');
        }

        return $this->parseDate($this->filter->value)->format('M j, Y');
    }

    protected function getValue(): mixed
    {
        if (! $this->filter?->value) {
            return null;
        }

        if ($this->isTimeframe) {
            $dates = $this->getTimeframeDatesFromValue($this->filter->value);

            if (! $dates) {
                return null;
            }

            ['start' => $startDate, 'end' => $endDate] = $dates;

            return [
                'start' => $this->formatDate($startDate),
                'end' => $this->formatDate($endDate),
            ];
        }

        return $this->formatDate($this->parseDate($this->filter->value));
    }

    public function getDefaultValue(): mixed
    {
        $default = parent::getDefaultValue();

        if (! $default) {
            return null;
        }

        if ($this->isTimeframe) {
            $dates = $this->getTimeframeDatesFromValue($default);

            if (! $dates) {
                return null;
            }

            ['start' => $startDate, 'end' => $endDate] = $dates;

            return [
                'start' => $this->formatDate($startDate),
                'end' => $this->formatDate($endDate),
            ];
        }

        return $this->formatDate($this->parseDate($default));
    }

    protected function formatDate(CarbonInterface $date): string
    {
        return $this->evaluate(
            value: $this->formatDateUsing,
            named: [
                'date' => $date,
            ],
            typed: [
                CarbonInterface::class => $date,
                Carbon::class => $date,
            ],
            positional: [
                0 => $date,
            ],
        );
    }

    protected function getFormattedSuggestions(): array
    {
        return collect($this->suggestions)
            ->map(function (TimeSuggestion|TimeframeSuggestion $suggestion) {
                if ($suggestion instanceof TimeSuggestion) {
                    return [
                        'type' => 'time',
                        'label' => $suggestion->label,
                        'date' => $this->formatDate($suggestion->date),
                    ];
                }

                if ($suggestion instanceof TimeframeSuggestion) {
                    return [
                        'type' => 'timeframe',
                        'label' => $suggestion->label,
                        'start' => $this->formatDate($suggestion->start),
                        'end' => $this->formatDate($suggestion->end),
                    ];
                }

                return $suggestion;
            })
            ->all();
    }
}
