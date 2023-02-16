<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\Resolver;

use FishPig\WordPressGraphQl\Model\DataProvider\Post as PostDataProvider;
use FishPig\WordPress\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class Posts extends \FishPig\WordPressGraphQl\Model\Resolver\Collection\AbstractResolver
{
    /**
     * @var PostDataProvider
     */
    private $dataProvider = null;

    /**
     * @var PostCollectionFactory
     */
    private $collectionFactory = null;

    /**
     *
     */
    public function __construct(
        PostDataProvider $dataProvider,
        PostCollectionFactory $collectionFactory,
        \FishPig\WordPressGraphQl\Model\DataProvider\Term $termDataProvider
    ) {
        $this->dataProvider = $dataProvider;
        $this->collectionFactory = $collectionFactory;
        $this->termDataProvider = $termDataProvider;
    }

    /**
     *
     */
    protected function buildCollection(array $args): iterable
    {
        $posts = $this->collectionFactory->create()->addIsViewableFilter();

        if (!empty($args['id'])) {
            $posts->addFieldToFilter('ID', ['in' => $args['id']]);
            // We might as well set the page size here as it cannot be
            // more than count($args['id'])
            $posts->setPageSize(count($args['id']));
        }
        if (!empty($args['post_type'])) {
            $posts->addPostTypeFilter($args['post_type']);
        }

        if (!empty($args['term_id'])) {
            $posts->addTermIdFilter($args['term_id'], $args['term_taxonomy']);
        }

        if (!empty($args['user_id'])) {
            $posts->addUserIdFilter($args['user_id']);
        }

        return $posts;
    }

    /**
     *
     */
    protected function prepareItemData(object $item, array $args = []): iterable
    {
        $data = $this->dataProvider->getData($item);;

        if (!empty($args['withTaxonomies'])) {
            $data['terms'] = $this->termDataProvider->getListByPost(
                $data['model'],
                $args['withTaxonomies']
            );
        }

        return $data;
    }

    /**
     *
     */
    protected function validateInput(array $args): array
    {
        if (isset($args['term_id']) && !isset($args['term_taxonomy'])) {
            throw new GraphQlInputException(
                __(
                    'If you filter by term_id, you must also specify term_taxonomy.'
                )
            );
        }

        return $args;
    }
}
