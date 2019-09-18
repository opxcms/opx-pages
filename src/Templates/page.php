<?php

namespace Modules\Shop\Categories\Templates;

use Core\Foundation\Template\Template;
use Modules\Opx\Pages\Models\Page;
use Modules\Opx\Pages\OpxPages;
use Modules\Shop\Categories\Models\Category;
use Modules\Shop\Properties\Models\Group;

/**
 * HELP:
 *
 * ID parameter is shorthand for defining module and field name separated by `::`.
 * [$module, $name] = explode('::', $id, 2);
 * $captionKey = "{$module}::template.section_{$name}";
 *
 * PLACEMENT is shorthand for section and group of field separated by `/`.
 * [$section, $group] = explode('/', $placement);
 *
 * PERMISSIONS is shorthand for read permission and write permission separated by `|`.
 * [$readPermission, $writePermission] = explode('|', $permissions, 2);
 */

return [
    'sections' => [
        Template::section('content'),
        Template::section('images'),
        Template::section('general'),
        Template::section('seo'),
    ],
    'groups' => [
        Template::group('common'),
        Template::group('templates'),
        Template::group('publication'),
        Template::group('timestamps'),
        Template::group('robots'),
        Template::group('sitemap'),
    ],
    'fields' => [

        // name
        Template::string('name', 'content/', '', [], '', 'required'),
        // content
        Template::html('content', 'content/'),

        // images
        Template::image('image', 'images/', true, 'images', 'page_', '', 'max:1'),
        Template::image('images', 'images/', true, 'images', 'page_', '', 'max:1'),

        // id
        Template::id('id', 'general/common', 'fields.id_info'),
        // parentId
        Template::parent('parent_id', 'general/common', Template::makeNestedList(Page::class)),
        // alias
        Template::string('alias', 'general/common', '', ['counter' => ['max' => 100]], '', 'required|alpha_dash|max:100'),
        // templates
        Template::select('template', 'general/templates', null, OpxPages::getTemplatesList(), false, '', 'required', '', ['needs_reload' => true]),
        Template::select('child_template', 'general/templates', null, OpxPages::getTemplatesList()),
        Template::select('layout', 'general/templates', null, OpxPages::getViewsList(), false, '', 'required'),
        Template::select('child_layout', 'general/templates', null, OpxPages::getViewsList()),

        // publication
        Template::publicationPublished(),
        Template::publicationPublishStart(),
        Template::publicationPublishEnd(),

        // timestamps
        Template::timestampCreatedAt(),
        Template::timestampUpdatedAt(),
        Template::timestampDeletedAt(),

        // seo
        Template::metaTitle(),
        Template::metaKeywords(),
        Template::metaDescription(),

        // robots
        Template::robotsNoIndex(),
        Template::robotsNoFollow(),
        Template::robotsCanonical(),

        // sitemap
        Template::sitemapEnable(),
        Template::sitemapUpdateFrequency(),
        Template::sitemapPriority(),
        Template::sitemapLastModEnable(),
    ],
];
