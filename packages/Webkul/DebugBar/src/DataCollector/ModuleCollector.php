<?php

namespace Webkul\DebugBar\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\Renderable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use Konekt\Concord\Facades\Concord;

/**
 * Collector for UnoPim's Module Collector
 */
class ModuleCollector extends DataCollector implements AssetProvider, DataCollectorInterface, Renderable
{
    public $models = [];

    public $views = [];

    public $queries = [];

    public $count = 0;

    public function __construct(
        Dispatcher $events,
        PDOCollector $pdoCollector
    ) {
        $events->listen('eloquent.*', function ($event, $models): void {
            if (Str::contains($event, 'eloquent.retrieved')) {
                foreach (array_filter($models) as $model) {
                    $class = $model::class;
                    $this->models[$class] = ($this->models[$class] ?? 0) + 1;
                    $this->count++;
                }
            }
        });

        $events->listen('composing:*', function ($view, $data = []): void {
            $view = $data ? $data[0] : $view;

            $this->views[] = $this->trimViewName($view->getName(), $view->getPath());
        });

        app()['db']->listen(
            function ($query, $bindings = null, $time = null, $connectionName = null) use ($pdoCollector): void {
                $this->queries[] = [
                    'sql'          => $this->addQueryBindings($query),
                    'duration'     => $query->time,
                    'duration_str' => $pdoCollector->formatDuration($query->time),
                    'connection'   => $query->connection->getDatabaseName(),
                ];
            }
        );
    }

    /**
     * @param  QueryExecuted  $query
     * @return string
     */
    public function addQueryBindings($query)
    {
        $sql = $query->sql;

        $bindings = $this->checkBindings($query->connection->prepareBindings($query->bindings));

        if (! empty($bindings)) {
            foreach ($bindings as $key => $binding) {
                $regex = is_numeric($key)
                    ? "/\?(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/"
                    : "/:{$key}(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";

                if (
                    ! is_int($binding)
                    && ! is_float($binding)
                ) {
                    $binding = $query->connection->getPdo()->quote($binding ?? '');
                }

                $sql = preg_replace($regex, $binding, (string) $sql, 1);
            }
        }

        return $sql;
    }

    /**
     * Check bindings for illegal (non UTF-8) strings, like Binary data.
     *
     * @param  array  $bindings
     * @return mixed
     */
    public function checkBindings($bindings)
    {
        foreach ($bindings as &$binding) {
            if (
                is_string($binding)
                && ! mb_check_encoding($binding, 'UTF-8')
            ) {
                $binding = '[BINARY DATA]';
            }
        }

        return $bindings;
    }

    /**
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    public function trimViewName($name, $path)
    {
        if ($path) {
            $path = ltrim(str_replace(base_path(), '', realpath($path)), '/');
        }

        return $path !== '' && $path !== '0' ? sprintf('%s (%s)', $name, $path) : $name;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(): array
    {
        $modules = [];

        foreach (Concord::getModules() as $module) {
            $models = $this->getModels($module->getNamespaceRoot());

            $views = $this->getTemplates($module->getNamespaceRoot());

            $queries = $this->getQueries($module->getNamespaceRoot());

            if (
                count($models)
                || count($views)
                || count($queries)
            ) {
                $modules[] = [
                    'name'    => $module->getNamespaceRoot(),
                    'models'  => $models,
                    'views'   => $views,
                    'queries' => $queries,
                ];
            }
        }

        return [
            'count'   => count($modules),
            'modules' => $modules,
        ];
    }

    /**
     * @param  string  $classNamespace
     */
    public function getModels($classNamespace): array
    {
        $models = [];

        foreach ($this->models as $model => $count) {
            if (str_contains((string) $model, $classNamespace.'\\')) {
                $models[] = $model.' ('.$count.')';
            }
        }

        return $models;
    }

    /**
     * @param  string  $classNamespace
     */
    public function getTemplates($classNamespace): array
    {
        $viewNamespace = Str::lower(class_basename($classNamespace));

        $classNamespace = str_replace('\\', '/', $classNamespace).'/';

        $views = [];

        foreach ($this->views as $view) {
            if (str_contains((string) $view, $classNamespace)) {
                $views[] = $view;
            } elseif (str_contains((string) $view, 'resources/themes/'.$viewNamespace.'/')) {
                $views[] = $view;
            } elseif (str_contains((string) $view, 'resources/admin-themes/'.$viewNamespace.'/')) {
                $views[] = $view;
            } elseif (str_contains((string) $view, 'resources/vendor/views/'.$viewNamespace.'/')) {
                $views[] = $view;
            }
        }

        return $views;
    }

    /**
     * @param  string  $classNamespace
     */
    public function getQueries($classNamespace): array
    {
        $moduleTables = $this->getDatabaseTables($classNamespace);

        $queries = [];

        foreach ($this->queries as $query) {
            $sqlParts = explode(' ', str_replace('`', '', $query['sql']));

            $tableName = $sqlParts[array_search('from', $sqlParts) + 1];

            if (in_array($tableName, $moduleTables)) {
                $queries[] = $query;
            }
        }

        return $queries;
    }

    /**
     * @param  string  $classNamespace
     */
    public function getDatabaseTables($classNamespace): array
    {
        $tables = [];

        foreach (Concord::getModelBindings() as $model) {
            if (str_contains($model, $classNamespace.'\\')) {
                $tables[] = resolve($model)->getTable();
            }
        }

        return $tables;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'modules';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets(): array
    {
        return [
            'modules'       => [
                'icon'    => 'cubes',
                'widget'  => 'PhpDebugBar.Widgets.ModulesWidget',
                'map'     => 'modules',
                'default' => '[]',
            ],

            'modules:badge' => [
                'map'     => 'modules.count',
                'default' => 0,
            ],
        ];
    }

    public function getAssets(): array
    {
        return [
            'base_path' => __DIR__.'/../Resources/',
            'base_url'  => __DIR__.'/../Resources/',
            'css'       => 'widgets/modules/widget.css',
            'js'        => 'widgets/modules/widget.js',
        ];
    }
}
