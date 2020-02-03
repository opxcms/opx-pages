<?php

namespace Modules\Opx\Pages\Controllers;

use Core\Foundation\ListHelpers\Filters;
use Core\Foundation\ListHelpers\Orders;
use Core\Foundation\ListHelpers\Search;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Core\Http\Controllers\APIListController;
use Modules\Admin\Authorization\AdminAuthorization;
use Modules\Opx\Pages\Models\Page;

class ManagePagesListApiController extends APIListController
{
    use FormatPageTrait;

    protected $caption = 'opx_pages::manage.pages';
    protected $description;
    protected $source = 'manage/api/module/opx_pages/pages_list/pages';
    protected $children = true;

    protected $order = [
        'current' => 'id',
        'direction' => 'asc',
        'fields' => [
            'id' => 'orders.sort_by_id',
            'creation_date' => 'orders.sort_by_creation_date',
            'update_date' => 'orders.sort_by_update_date',
            'delete_date' => 'orders.sort_by_delete_date',
            'name' => 'orders.sort_by_name',
            'alias' => 'orders.sort_by_alias',
            'published' => 'orders.sort_by_published',
            'publish_start' => 'orders.sort_by_publish_start',
            'publish_end' => 'orders.sort_by_publish_end',
        ],
    ];

    public $search = [
        'id' => [
            'caption' => 'opx_pages::manage.search_by_id',
            'default' => true,
        ],
        'name' => [
            'caption' => 'opx_pages::manage.search_by_name',
            'default' => true,
        ],
        'alias' => [
            'caption' => 'opx_pages::manage.search_by_alias',
            'default' => true,
        ],
    ];

    /**
     * Get list of users with sorting, filters and search.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function postPages(Request $request): JsonResponse
    {
        if(!AdminAuthorization::can('opx_pages::list')) {
            return $this->returnNotAuthorizedResponse();
        }

        $order = $request->input('order');
        $filters = $request->input('filters');
        $search = $request->input('search');

        if (empty($filters['show_all'])) {
            $parentId = $request->input('parent_id', 0);
        } else {
            $parentId = null;
        }

        $pages = $this->makeQuery($parentId);

        $pages = $this->applyOrder($pages, $order);
        $pages = $this->applyFilters($pages, $filters);
        $pages = $this->applySearch($pages, $search);

        $pages = $pages->paginate(50);

        /** @var Collection $pages */
        if ($pages->count() > 0) {
            $pages->transform(function ($page) {
                return $this->formatPage($page);
            });
        }

        $response = $pages->toArray();

        if (!empty($parentId)) {
            /** @var Page $parent */
            $parent = Page::withTrashed()->where('id', $parentId)->first();
            if ($parent !== null) {
                $response['parent'] = $parent->getAttribute('parent_id');
                $response['description'] = $parent->getAttribute('name');
            }
        }

        return response()->json($response);
    }

    /**
     * Make base list query.
     *
     * @param int|null $parentId
     *
     * @return  EloquentBuilder
     */
    protected function makeQuery(int $parentId = null): EloquentBuilder
    {
        /** @var EloquentBuilder $query */
        $query = Page::query()->select('pages.*')->withCount('children');
        $query->when($parentId !== null, static function ($query) use ($parentId) {
            /** @var EloquentBuilder $query */
            $query->where('parent_id', $parentId);
        });

        return $query;
    }

    /**
     * Apply order to query.
     *
     * @param EloquentBuilder $query
     * @param array $order
     *
     * @return  EloquentBuilder
     */
    protected function applyOrder(EloquentBuilder $query, $order): EloquentBuilder
    {
        $direction = Orders::getDirection($order);

        switch ($order['by'] ?? '') {
            case 'name':
                $query = Orders::processSimpleOrder($query, 'name', $direction);
                break;
            case 'alias':
                $query = Orders::processSimpleOrder($query, 'alias', $direction);
                break;
            case 'published':
                $query = Orders::processPublishedOrder($query, $direction);
                break;
            case 'creation_date':
                $query = Orders::processDateOrder($query, 'created_at', $direction);
                break;
            case 'update_date':
                $query = Orders::processDateOrder($query, 'updated_at', $direction);
                break;
            case 'delete_date':
                $query = Orders::processDateOrder($query, 'deleted_at', $direction);
                break;
            case 'publish_start':
                $query = Orders::processDateOrder($query, 'publish_start', $direction);
                break;
            case 'publish_end':
                $query = Orders::processDateOrder($query, 'publish_end', $direction);
                break;
            case 'id':
            default:
                $query = Orders::processSimpleOrder($query, 'id', $direction);
        }
        return $query;
    }

    /**
     * Apply filters to query.
     *
     * @param EloquentBuilder $query
     * @param array $filters
     *
     * @return  EloquentBuilder
     */
    protected function applyFilters(EloquentBuilder $query, $filters): EloquentBuilder
    {
        $query = Filters::processPublishedFilter($query, $filters);
        $query = Filters::processDeletedFilter($query, $filters);

        return $query;
    }

    /**
     * Apply search to query.
     *
     * @param EloquentBuilder $query
     * @param array $search
     *
     * @return  EloquentBuilder
     */
    protected function applySearch(EloquentBuilder $query, $search): EloquentBuilder
    {
        return Search::applySearch($query, $search, ['id', 'name', 'alias']);
    }

    /**
     * Get add link.
     *
     * @return  string
     */
    protected function getAddLink(): ?string
    {
        return AdminAuthorization::can('opx_pages::add') ? 'opx_pages::pages_add' : null;
    }

    /**
     * Get edit link.
     *
     * @return  string
     */
    protected function getEditLink(): ?string
    {
        return AdminAuthorization::can('opx_pages::edit') ? 'opx_pages::pages_edit' : null;
    }

    /**
     * Get edit link.
     *
     * @return  string
     */
    protected function getEnableLink(): ?string
    {
        return AdminAuthorization::can('opx_pages::disable') ? 'manage/api/module/opx_pages/pages_actions/enable' : null;
    }

    /**
     * Get edit link.
     *
     * @return  string
     */
    protected function getDisableLink(): ?string
    {
        return AdminAuthorization::can('opx_pages::disable') ? 'manage/api/module/opx_pages/pages_actions/disable' : null;
    }

    /**
     * Get edit link.
     *
     * @return  string
     */
    protected function getDeleteLink(): ?string
    {
        return AdminAuthorization::can('opx_pages::delete') ? 'manage/api/module/opx_pages/pages_actions/delete' : null;
    }

    /**
     * Get edit link.
     *
     * @return  string
     */
    protected function getRestoreLink(): ?string
    {
        return AdminAuthorization::can('opx_pages::delete') ? 'manage/api/module/opx_pages/pages_actions/restore' : null;
    }
}