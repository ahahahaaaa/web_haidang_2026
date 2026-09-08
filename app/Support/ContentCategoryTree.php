<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Src\Domains\Cms\Models\ContentCategory;

class ContentCategoryTree
{
    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return Collection<int, ContentCategory>
     */
    public static function nest(Collection $categories): Collection
    {
        $sorted = self::sorted($categories);
        $grouped = $sorted->groupBy(fn (ContentCategory $category) => (int) ($category->parent_id ?? 0));
        $knownIds = $sorted->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $sorted
            ->filter(function (ContentCategory $category) use ($knownIds): bool {
                $parentId = (int) ($category->parent_id ?? 0);

                return $parentId === 0 || ! in_array($parentId, $knownIds, true);
            })
            ->values()
            ->map(fn (ContentCategory $category) => self::hydrateNode($category, $grouped, 0));
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return Collection<int, ContentCategory>
     */
    public static function flatten(Collection $categories): Collection
    {
        return self::flattenNested(self::nest($categories));
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return array<int, int>
     */
    public static function descendantIds(Collection $categories, int $parentId, bool $includeSelf = true): array
    {
        $grouped = self::sorted($categories)->groupBy(fn (ContentCategory $category) => (int) ($category->parent_id ?? 0));
        $ids = $includeSelf ? [$parentId] : [];

        $walk = function (int $currentId) use (&$walk, &$ids, $grouped): void {
            foreach ($grouped->get($currentId, collect()) as $child) {
                $childId = (int) $child->getKey();
                $ids[] = $childId;
                $walk($childId);
            }
        };

        $walk($parentId);

        return array_values(array_unique($ids));
    }

    public static function pathLabel(?ContentCategory $category, string $separator = ' / '): string
    {
        return implode($separator, self::pathCategories($category)->pluck('name')->all());
    }

    /**
     * @return Collection<int, ContentCategory>
     */
    public static function pathCategories(?ContentCategory $category): Collection
    {
        $trail = collect();
        $current = $category;
        $seen = [];

        while ($current instanceof ContentCategory) {
            $currentId = (int) $current->getKey();

            if ($currentId > 0 && in_array($currentId, $seen, true)) {
                break;
            }

            if ($currentId > 0) {
                $seen[] = $currentId;
            }

            $trail->prepend($current);

            $parent = $current->relationLoaded('parent')
                ? $current->getRelation('parent')
                : $current->parent()->first();

            $current = $parent instanceof ContentCategory ? $parent : null;
        }

        return $trail->values();
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return Collection<int, ContentCategory>
     */
    public static function applyBranchCounts(
        Collection $categories,
        string $directCountAttribute = 'blog_posts_count',
        string $branchCountAttribute = 'branch_blog_posts_count',
    ): Collection {
        $tree = self::nest($categories);
        self::setBranchCounts($tree, $directCountAttribute, $branchCountAttribute);

        return $tree;
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return Collection<int, ContentCategory>
     */
    protected static function sorted(Collection $categories): Collection
    {
        return $categories
            ->sortBy(function (ContentCategory $category): string {
                return sprintf(
                    '%010d|%s|%010d',
                    (int) ($category->sort_order ?? 0),
                    mb_strtolower(trim((string) $category->name)),
                    (int) $category->getKey(),
                );
            })
            ->values();
    }

    /**
     * @param  Collection<int, Collection<int, ContentCategory>>  $grouped
     */
    protected static function hydrateNode(ContentCategory $category, Collection $grouped, int $depth): ContentCategory
    {
        $children = $grouped->get((int) $category->getKey(), collect())
            ->values()
            ->map(fn (ContentCategory $child) => self::hydrateNode($child, $grouped, $depth + 1));

        $category->setRelation('children', $children);
        $category->setAttribute('tree_depth', $depth);

        return $category;
    }

    /**
     * @param  Collection<int, ContentCategory>  $nodes
     * @return Collection<int, ContentCategory>
     */
    protected static function flattenNested(Collection $nodes): Collection
    {
        return $nodes
            ->flatMap(function (ContentCategory $category): Collection {
                $children = $category->relationLoaded('children')
                    ? collect($category->getRelation('children'))
                    : collect();

                return collect([$category])->concat(self::flattenNested($children));
            })
            ->values();
    }

    /**
     * @param  Collection<int, ContentCategory>  $nodes
     */
    protected static function setBranchCounts(
        Collection $nodes,
        string $directCountAttribute,
        string $branchCountAttribute,
    ): int {
        $total = 0;

        foreach ($nodes as $category) {
            $children = $category->relationLoaded('children')
                ? collect($category->getRelation('children'))
                : collect();

            $branchCount = (int) ($category->{$directCountAttribute} ?? 0)
                + self::setBranchCounts($children, $directCountAttribute, $branchCountAttribute);

            $category->setAttribute($branchCountAttribute, $branchCount);
            $total += $branchCount;
        }

        return $total;
    }
}
