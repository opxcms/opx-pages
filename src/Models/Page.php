<?php

namespace Modules\Opx\Pages\Models;

use Core\Traits\Model\DataAttribute;
use Core\Traits\Model\GetContent;
use Core\Traits\Model\GetImage;
use Core\Traits\Model\Publishing;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use Publishing,
        SoftDeletes,
        DataAttribute,
        GetContent,
        GetImage;

    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 'publish_start', 'publish_end',
    ];

    /**
     * Get all subcategories.
     *
     * @return  HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get all published children.
     *
     * @return  Relation
     */
    public function publishedChildren(): Relation
    {
        return self::addPublishingToQuery($this->children());
    }

    /**
     * Get link to page.
     *
     * @return  string|null
     */
    public function link(): ?string
    {
        $name = 'opx_pages::page::' . $this->getAttribute('id');

        return route($name, [], false);
    }
}