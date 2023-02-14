<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\DataProvider;

use FishPig\WordPress\Model\Term as TermModel;
use FishPig\WordPress\Model\Taxonomy as TaxonomyModel;
use FishPig\WordPress\Model\Post as PostModel;

class Term
{
    /**
     *
     */
    private $termRepository = null;

    /**
     *
     */
    private $taxonomyRepository = null;

    /**
     *
     */
    public function __construct(
        \FishPig\WordPress\Model\TermRepository $termRepository,
        \FishPig\WordPress\Model\TaxonomyRepository $taxonomyRepository,
        \FishPig\WordPress\Model\ResourceModel\Term\CollectionFactory $termCollectionFactory
    ) {
        $this->termRepository = $termRepository;
        $this->taxonomyRepository = $taxonomyRepository;
        $this->termCollectionFactory = $termCollectionFactory;
    }

    /**
     *
     */
    public function getData(TermModel $term, array $fields = null): array
    {
        $taxonomy = $term->getTaxonomyInstance();

        return [
            '_model' => $term,
            'id' => (int)$term->getId(),
            'name' => $term->getName(),
            'slug' => $term->getSlug(),
            'taxonomy_id' => (int)$taxonomy->getId(),
            'taxonomy' => $taxonomy->getTaxonomy(),
            'description' => $taxonomy->getDescription(),
            'parent' => (int)$taxonomy->getParent(),
            'count' => (int)$taxonomy->getCount()
        ];
    }

    /**
     *
     */
    public function getDataById($id, array $fields = null): array
    {
        try {
            return $this->getData(
                $this->termRepository->get((int)$id),
                $fields
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     *
     */
    public function getDataBySlug(string $slug, array $fields = null): array
    {
        try {
            return $this->getData(
                $this->termRepository->getByField($slug, 'slug'),
                $fields
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     *
     */
    public function getListByPost(PostModel $post, $slugs): array
    {
        $slugs = (array)$slugs;
        $data = [];

        foreach ($slugs as $slug) {
            $result = [
                'taxonomy' => $slug,
                'total_count' => 0,
                'items' => []
            ];

            if ($taxonomy = $this->getTaxonomyBySlug($slug)) {
                $terms = $this->termCollectionFactory->create()
                    ->addTaxonomyFilter($slug)
                    ->addObjectIdFilter($post->getId())
                    ->load();

                foreach ($terms as $term) {
                    $result['items'][] = $this->getData($term);
                }

                $result['total_count'] = count($result['items']);
            }

            $data[$slug] = $result;
        }

        return $data;
    }

    /**
     *
     */
    private function getTaxonomyBySlug(string $slug): ?TaxonomyModel
    {
        foreach ($this->taxonomyRepository->getAll() as $taxonomy) {
            if ($taxonomy->getTaxonomy() === $slug) {
                return $taxonomy;
            }
        }

        return null;
    }
}
