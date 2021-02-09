<?php

namespace Modules\Opx\Pages\Controllers;

use Core\Events\RouteChanged;
use Core\Http\Controllers\ApiActionsController;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use JsonException;
use Modules\Admin\Authorization\AdminAuthorization;
use Modules\Opx\Pages\Models\Page;

class ManagePagesActionsApiController extends ApiActionsController
{
    use FormatPageTrait;

    /**
     * Delete pages with given ids.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function postDelete(Request $request): JsonResponse
    {
        if(!AdminAuthorization::can('opx_pages::delete')) {
            return $this->returnNotAuthorizedResponse();
        }

        $ids = $request->all();

        /** @var EloquentBuilder $pages */
        $pages = Page::query()->whereIn('id', $ids)->get();

        if ($pages->count() > 0) {
            /** @var Page $page */
            foreach ($pages as $page) {
                $page->delete();
            }
        }

        event(new RouteChanged());

        return response()->json(['message' => 'success']);
    }

    /**
     * Restore pages with given ids.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function postRestore(Request $request): JsonResponse
    {
        if(!AdminAuthorization::can('opx_pages::delete')) {
            return $this->returnNotAuthorizedResponse();
        }

        $ids = $request->all();

        /** @var EloquentBuilder $pages */
        $pages = Page::query()->whereIn('id', $ids)->onlyTrashed()->get();

        if ($pages->count() > 0) {
            /** @var Page $page */
            foreach ($pages as $page) {
                $page->restore();
            }
        }

        event(new RouteChanged());

        return response()->json(['message' => 'success']);
    }

    /**
     * Publish pages with given ids and clear publishing limitation dates if need.
     * Returns response with corrected pages.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     * @throws JsonException
     */
    public function postEnable(Request $request): JsonResponse
    {
        if(!AdminAuthorization::can('opx_pages::disable')) {
            return $this->returnNotAuthorizedResponse();
        }

        $ids = $request->all();

        /** @var EloquentBuilder $pages */
        $pages = Page::query()->withCount('children')->whereIn('id', $ids)->get();

        $changed = [];

        if ($pages->count() > 0) {
            /** @var Page $page */
            foreach ($pages as $page) {
                if (!$page->isPublished()) {
                    $page->publish();
                    $page->save();
                    $changed[$page->getAttribute('id')] = $this->formatPage($page);
                }
            }
        }

        return response()->json([
            'message' => 'success',
            'changed' => $changed,
        ]);
    }

    /**
     * Mark pages as unpublished with given ids.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     * @throws JsonException
     */
    public function postDisable(Request $request): JsonResponse
    {
        if(!AdminAuthorization::can('opx_pages::disable')) {
            return $this->returnNotAuthorizedResponse();
        }

        $ids = $request->all();

        /** @var EloquentBuilder $pages */
        $pages = Page::query()->whereIn('id', $ids)->get();

        if ($pages->count() > 0) {
            /** @var Page $page */
            foreach ($pages as $page) {
                if ($page->isPublished()) {
                    $page->unPublish();
                    $page->save();
                }
            }
        }

        return response()->json([
            'message' => 'success',
        ]);
    }
}