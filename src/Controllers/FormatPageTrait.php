<?php

namespace Modules\Opx\Pages\Controllers;

use Modules\Opx\Pages\Models\Page;

trait FormatPageTrait
{
    /**
     * Format user record for displaying in list.
     *
     * @param Page $page
     *
     * @return  array
     */
    protected function formatPage(Page $page): array
    {
        $id = $page->getAttribute('id');
        $name = $page->getAttribute('name');
        $alias = $page->getAttribute('alias');
        $enabled = $page->isPublished();
        $isDeleted = $page->getAttribute('deleted_at') !== null;
        $childrenCount = $page->getAttribute('children_count');

        $props = [];
        if ($page->getAttribute('publish_start') !== null) {
            $props[] = trans('manage.publish_start') . ': ';
            $props[] = 'datetime:' . $page->getAttribute('publish_start')->toIso8601String();
        }
        if ($page->getAttribute('publish_end') !== null) {
            $props[] = trans('manage.publish_end') . ': ';
            $props[] = 'datetime:' . $page->getAttribute('publish_end')->toIso8601String();
        }
        return $this->makeListRecord(
            $id,
            $name,
            $alias,
            null,
            $props,
            $enabled,
            $isDeleted,
            $childrenCount
        );
    }
}