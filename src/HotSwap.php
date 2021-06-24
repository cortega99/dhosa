<?php

namespace Laravie\Dhosa;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class HotSwap
{
    /**
     * List of swappable models.
     *
     * @var array
     */
    protected static $swappable = [];

    /**
     * Register swappable model.
     *
     * @param  array|string  $class
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function register($class): void
    {
        foreach ((array) $class as $model) {
            static::validateClassIsEloquentModel($model);
            static::validateClassIsSwappable($model);

            static::$swappable[$model::hsAliasName()] = $model;
        }
    }

    /**
     * Override swappable model.
     *
     * @param  string  $alias
     * @param  string|null  $class
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function override(string $alias, ?string $class = null): void
    {
        if (\is_null($class)) {
            static::validateClassIsSwappable($alias);

            $class = $alias;
            $alias = $class::hsAliasName();
        }

        static::validateClassIsEloquentModel($class);

        static::$swappable[$alias] = $class;
    }

    /**
     * Resolve model class name.
     *
     * @param  string  $alias
     *
     * @return string|null
     */
    public static function eloquent(string $alias): ?string
    {
        return \array_key_exists($alias, static::$swappable) ? static::$swappable[$alias] : null;
    }

    /**
     * Make a model instance.
     *
     * @param  string  $alias
     * @param  array  $attributes
     *
     * @throws \InvalidArgumentException
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function make(string $alias, array $attributes = []): ?Model
    {
        $class = static::eloquent($alias);

        if (\is_null($class)) {
            return null;
        }

        return new $class($attributes);
    }

    /**
     * Flush hot-swap mapping.
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$swappable = [];
    }

    /**
     * Validate class is an eloquent model.
     *
     * @param  string  $class
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    private static function validateClassIsEloquentModel(string $class): void
    {
        if (! is_subclass_of($class, Model::class)) {
            throw new InvalidArgumentException(sprintf('Given [%s] is not a subclass of [%s].', $class, Model::class));
        }
    }

    /**
     * Validate class is an eloquent model.
     *
     * @param  string  $class
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    private static function validateClassIsSwappable(string $class): void
    {
        $uses = class_uses_recursive($class);

        if (! isset($uses[Concerns\Swappable::class])) {
            throw new InvalidArgumentException(sprintf("Given [%s] doesn't use [%s] trait.", $class, Concerns\Swappable::class));
        }
    }
}
