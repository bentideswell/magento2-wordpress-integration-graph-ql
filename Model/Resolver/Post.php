<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\Resolver;

use FishPig\WordPress\Model\Post as PostModel;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class Post implements ResolverInterface
{
    /**
     * @var \FishPig\WordPressGraphQl\Model\DataProvider\Post
     */
    private $postDataProvider = null;

    /**
     *
     */
    public function __construct(
        \FishPig\WordPressGraphQl\Model\DataProvider\Post $postDataProvider,
        \FishPig\WordPressGraphQl\Model\DataProvider\Term $termDataProvider
    ) {
        $this->postDataProvider = $postDataProvider;
        $this->termDataProvider = $termDataProvider;
    }

    /**
     *
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $data = $this->postDataProvider->getDataById(
            (int)$args['id'],
            $info->getFieldSelection()
        );

        if (!empty($args['withTaxonomies'])) {
            $data['terms'] = $this->termDataProvider->getListByPost(
                $data['_model'],
                $args['withTaxonomies']
            );
        }

        return $data;
    }
}
