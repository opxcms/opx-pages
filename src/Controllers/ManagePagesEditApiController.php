<?php

namespace Modules\Opx\Pages\Controllers;

use Core\Events\RouteChanged;
use Core\Foundation\Templater\Templater;
use Core\Http\Controllers\APIFormController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Opx\Pages\Models\Page;
use Modules\Opx\Pages\OpxPages;

class ManagePagesEditApiController extends APIFormController
{
    public $addCaption = 'opx_pages::manage.add_page';
    public $editCaption = 'opx_pages::manage.edit_page';
    public $create = 'manage/api/module/opx_pages/pages_edit/create';
    public $save = 'manage/api/module/opx_pages/pages_edit/save';
    public $redirect = '/pages/edit/';

    /**
     * Make category add form.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function getAdd(Request $request): JsonResponse
    {
        $parentId = $request->input('parent_id', 0);
        $templateName = $request->input('template');
        $initiator = $request->input('__initiator');

        $name = null;
        $layout = null;

        if ($initiator === 'template') {
            // get directly set template
            $name = $templateName ?? 'page';
            $layout = $templateName ?? 'page.blade.php';

            // In case of parent change we need to switch new template
        } else if ($parentId !== 0 || $initiator === 'parent_id') {
            // get template from parent page settings
            /** @var Page $parentPage */
            $parentPage = Page::where('id', $parentId)->first();
            $name = $parentPage !== null ? $parentPage->getAttribute('child_template') : 'page';
            $layout = $parentPage !== null ? $parentPage->getAttribute('child_layout') : 'page.blade.php';

        }

        // get default template and layout if not previously set
        $name = empty($name) ? 'page' : $name;
        $layout = empty($layout) ? 'page.blade.php' : $layout;

        $template = new Templater(OpxPages::getTemplateFileName($name));

        $template->fillDefaults();
        $template->setValues(['parent_id' => $parentId, 'template' => $name, 'layout' => $layout]);

        return $this->responseFormComponent(0, $template, $this->addCaption, $this->create);
    }

    /**
     * Make category edit form.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function getEdit(Request $request): JsonResponse
    {
        $parentId = $request->input('parent_id', 0);
        $templateName = $request->input('template');
        $initiator = $request->input('__initiator');
        $name = null;
        $layout = null;

        $id = $request->input('id');

        /** @var Page $page */
        $page = Page::withTrashed()->where('id', $id)->firstOrFail();

        // In case of parent change we need to switch new template
        if ($initiator === 'parent_id' && $parentId !== 0) {
            // get template from parent page settings
            /** @var Page $parentPage */
            $parentPage = Page::withTrashed()->where('id', $parentId)->first();
            $name = $parentPage !== null ? $parentPage->getAttribute('child_template') : 'page';
            $layout = $parentPage !== null ? $parentPage->getAttribute('child_layout') : 'page.blade.php';

        } elseif ($initiator === 'template') {
            // get directly set template
            $name = $templateName ?? 'page';
            $layout = $templateName ?? 'page.blade.php';

        } else {
            // get template assigned to page
            $name = $page->getAttribute('template');
            $layout = $page->getAttribute('layout');
        }
        $page->setAttribute('layout', $layout);
        $template = $this->makeTemplate($page, $name . '.php');

        return $this->responseFormComponent($id, $template, $this->editCaption, $this->save);
    }

    /**
     * Create new category.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function postCreate(Request $request): JsonResponse
    {
        if ($request->input('__reload') === true) {
            return $this->getAdd($request);
        }

        $name = $request->input('template', 'page');

        $template = new Templater(OpxPages::getTemplateFileName($name));

        $template->resolvePermissions();

        $template->fillValuesFromRequest($request);

        if (!$template->validate()) {
            return $this->responseValidationError($template->getValidationErrors());
        }

        $values = $template->getEditableValues();

        $page = $this->updatePageData(new Page(), $values);

        // Refill template
        $template = $this->makeTemplate($page, $name . '.php');
        $id = $page->getAttribute('id');

        return $this->responseFormComponent($id, $template, $this->editCaption, $this->save, $this->redirect . $id);
    }

    /**
     * Save category.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function postSave(Request $request): JsonResponse
    {
        if ($request->input('__reload') === true) {
            return $this->getEdit($request);
        }

        $id = $request->input('id');

        /** @var Page $page */
        $page = Page::withTrashed()->where('id', $id)->firstOrFail();
        $name = $request->input('template', 'page');

        $template = new Templater(OpxPages::getTemplateFileName($name));

        $template->resolvePermissions();

        $template->fillValuesFromRequest($request);

        if (!$template->validate(['id' => $page->getAttribute('id')])) {
            return $this->responseValidationError($template->getValidationErrors());
        }

        $values = $template->getEditableValues();

        $category = $this->updatePageData($page, $values);

        // Refill template
        $template = $this->makeTemplate($category, $name . '.php');

        return $this->responseFormComponent($id, $template, $this->editCaption, $this->save);
    }

    /**
     * Fill template with data.
     *
     * @param string $filename
     * @param Page $page
     *
     * @return  Templater
     */
    protected function makeTemplate(Page $page, $filename): Templater
    {
        $template = new Templater(OpxPages::getTemplateFileName($filename));

        $template->fillValuesFromObject($page);

        return $template;
    }

    /**
     * Update category data
     *
     * @param Page $page
     * @param array $data
     *
     * @return  Page
     */
    protected function updatePageData(Page $page, array $data): Page
    {
        $attributes = [
            'name', 'alias', 'parent_id',
            'image', 'images', 'content',
            'template', 'child_template', 'layout', 'child_layout',
            'published', 'publish_start', 'publish_end',
            'meta_title', 'meta_keywords', 'meta_description',
            'no_index', 'no_follow', 'canonical',
            'site_map_enable', 'site_map_update_frequency', 'site_map_priority', 'site_map_last_mod_enable',
        ];

        foreach (array_keys($data) as $entry) {
            if (strpos($entry, '_') === 0) {
                $attributes[] = $entry;
            }
        }

        $this->setAttributes($page, $data, $attributes);

        $new = !$page->exists;

        $page->save();

        $changed = $page->getChanges();

        if ($new || in_array('alias', $changed, true) || in_array('parent_id', $changed, true)) {
            event(new RouteChanged());
        }

        return $page;
    }

    /**
     * Upload image.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function postSaveImage(Request $request): JsonResponse
    {
        // TODO add ID to retrieve template ???
        return $this->storeImageFromRequest($request, OpxPages::getTemplateFileName('page.php'));
    }

    /**
     * Upload image.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function postCreateImage(Request $request): JsonResponse
    {
        // TODO add ID to retrieve template ???
        return $this->storeImageFromRequest($request, OpxPages::getTemplateFileName('page.php'));
    }
}