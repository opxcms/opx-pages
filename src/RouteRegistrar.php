<?php

namespace Modules\Opx\Pages;

use Core\Foundation\Module\RouteRegistrar as BaseRouteRegistrar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class RouteRegistrar extends BaseRouteRegistrar
{
    /** @var array */
    protected array $pages;

    /**
     * Handle public route registration.
     *
     * @param string $profile
     *
     * @return  void
     */
    public function registerPublicRoutes(string $profile): void
    {
        if(!$this->module->isMigrated()) {
            return;
        }

        $this->pages = DB::table('pages')
            ->select(['id', 'parent_id', 'alias'])
            ->whereNull('deleted_at')
            ->get()
            ->map(static function ($i) {
                return (array)$i;
            })
            ->groupBy('parent_id')
            ->toArray();

        if (empty($this->pages)) {
            return;
        }

        $namespace = class_exists('Templates\Opx\Pages\PageRenderController')
            ? 'Templates\Opx\Pages'
            : 'Modules\Opx\Pages\Controllers';
        Route::namespace($namespace)
            ->middleware('web')
            ->group(function () {
                $this->registerRecursive();
            });
    }

    /**
     * Register pages recursively.
     *
     * @param int $parent
     *
     * @return  void
     */
    protected function registerRecursive(int $parent = 0): void
    {
        if (empty($this->pages[$parent])) {
            return;
        }

        foreach ($this->pages[$parent] as $page) {
            Route::name("opx_pages::page::{$page['id']}")
                ->get($page['alias'], 'PageRenderController@renderModel');

            Route::prefix($page['alias'])
                ->group(function () use ($page) {
                    $this->registerRecursive($page['id']);
                });
        }
    }
}