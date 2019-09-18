<?php

namespace Modules\Opx\Pages\Controllers;

use Carbon\Carbon;
use Core\Foundation\ListHelpers\Filters;
use Core\Foundation\ListHelpers\Orders;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Core\Http\Controllers\APIListController;
use Modules\Opx\Pages\Models\Page;

class ManagePagesListApiController extends APIListController
{
    use FormatPageTrait;

    protected $caption = 'opx_pages::manage.pages';
    protected $description;
    protected $source = 'manage/api/module/opx_pages/pages_list/pages';


    protected $enable = 'manage/api/module/opx_pages/pages_actions/enable';
    protected $disable = 'manage/api/module/opx_pages/pages_actions/disable';
    protected $delete = 'manage/api/module/opx_pages/pages_actions/delete';
    protected $restore = 'manage/api/module/opx_pages/pages_actions/restore';

    protected $add = 'opx_pages::pages_add';
    protected $edit = 'opx_pages::pages_edit';

    protected $children = true;

    protected $filters = [
        'show_all' => [
            'caption' => 'filters.filter_by_show_all',
            'type' => 'switch',
            'enabled' => false,
            'value' => true,
        ],
        'published' => [
            'caption' => 'filters.filter_by_published',
            'type' => 'checkbox',
            'enabled' => false,
            'value' => 'published',
            'options' => ['published' => 'filters.filter_value_published', 'unpublished' => 'filters.filter_value_unpublished'],
        ],
        'show_deleted' => [
            'caption' => 'filters.filter_by_deleted',
            'type' => 'checkbox',
            'enabled' => false,
            'value' => 'show_deleted',
            'options' => ['show_deleted' => 'filters.filter_value_deleted', 'only_deleted' => 'filters.filter_value_only_deleted'],
        ],
    ];

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
//
//    public $search = [
//        'id' => [
//            'caption' => 'opx_users::manage.search_by_id',
//            'default' => true,
//        ],
//        'email' => [
//            'caption' => 'opx_users::manage.search_by_email',
//            'default' => true,
//        ],
//        'phone' => [
//            'caption' => 'opx_users::manage.search_by_phone',
//            'default' => true,
//        ],
//        'last_name' => [
//            'caption' => 'opx_users::manage.search_by_last_name',
//            'default' => true,
//        ],
//        'first_name' => [
//            'caption' => 'opx_users::manage.search_by_first_name',
//            'default' => false,
//        ],
//        'middle_name' => [
//            'caption' => 'opx_users::manage.search_by_middle_name',
//            'default' => false,
//        ],
//    ];

    /**
     * Get list of users with sorting, filters and search.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function postPages(Request $request): JsonResponse
    {
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
    public function applyFilters(EloquentBuilder $query, $filters): EloquentBuilder
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
//        if (!empty($search['subject']) && !empty($search['fields'])) {
//
//            $subject = str_replace('*', '%', $search['subject']);
//            $fields = explode(',', $search['fields']);
//
//            $query = $query->where(static function ($q) use ($fields, $subject) {
//                /** @var Builder $q */
//                if (in_array('id', $fields, true)) {
//                    $q->orWhere('users.id', 'LIKE', $subject);
//                }
//                if (in_array('email', $fields, true)) {
//                    $q->orWhere('users.email', 'LIKE', $subject);
//                }
//                if (in_array('phone', $fields, true)) {
//                    $q->orWhere('users.phone', 'LIKE', $subject);
//                }
//                if (in_array('last_name', $fields, true)) {
//                    $q->orWhere('user_details.last_name', 'LIKE', $subject);
//                }
//                if (in_array('first_name', $fields, true)) {
//                    $q->orWhere('user_details.first_name', 'LIKE', $subject);
//                }
//                if (in_array('middle_name', $fields, true)) {
//                    $q->orWhere('user_details.middle_name', 'LIKE', $subject);
//                }
//            });
//        }
        return $query;
    }
}