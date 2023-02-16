<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\Resolver\Post;

use FishPig\WordPressGraphQl\Model\DataProvider\Post\Comment as CommentDataProvider;
use FishPig\WordPress\Model\ResourceModel\Post\Comment\CollectionFactory as CommentCollectionFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class Comments extends \FishPig\WordPressGraphQl\Model\Resolver\Collection\AbstractResolver
{
    /**
     * @var CommentCollectionFactory
     */
    private $collectionFactory = null;

    /**
     * @var CommentDataProvider
     */
    private $dataProvider = null;

    /**
     *
     */
    public function __construct(
        CommentDataProvider $dataProvider,
        CommentCollectionFactory $collectionFactory
    ) {
        $this->dataProvider = $dataProvider;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     *
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $data = parent::resolve($field, $context, $info, $value, $args);
        $data['post_id'] = $args['post_id'];
        return $data;
    }

    /**
     *
     */
    protected function buildCollection(array $args): iterable
    {
        return $comments = $this->collectionFactory->create()
            ->addCommentApprovedFilter()
            ->addOrderByDate()
            ->addPostIdFilter((int)$args['post_id']);
    }

    /**
     *
     */
    protected function prepareItemData(object $item, array $args = []): iterable
    {
        return $this->dataProvider->getData($item);
    }

    /**
     *
     */
    protected function validateInput(array $args): array
    {
        if (empty($args['post_id']) || (int)$args['post_id'] <= 0) {
            throw new GraphQlInputException(
                __('Invalid or no post_id given.')
            );
        }

        return $args;
    }
}
