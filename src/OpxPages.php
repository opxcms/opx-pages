<?php

namespace Modules\Opx\Pages;

use Illuminate\Support\Facades\Facade;

/**
 * @method  static string name()
 * @method  static string get($key)
 * @method  static string path($path = '')
 * @method  static string getTemplateFileName(string $name)
 * @method  static string trans($key, $parameters = [], $locale = null)
 * @method  static array|string|null  config($key = null)
 * @method  static mixed view($view)
 * @method  static array getTemplatesList()
 * @method  static array getViewsList()
 */
class OpxPages extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'opx_pages';
    }
}
