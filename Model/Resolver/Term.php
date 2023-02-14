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

class Term implements ResolverInterface
{
    /**
     * @var \FishPig\WordPressGraphQl\Model\DataProvider\Term
     */
    private $termDataProvider = null;

    /**
     *
     */
    public function __construct(
        \FishPig\WordPressGraphQl\Model\DataProvider\Term $termDataProvider
    ) {
        $this->termDataProvider = $termDataProvider;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!empty($args['id']) && !empty($args['slug'])) {
            throw new GraphQlInputException(
                __('You cannot specify an id and slug filter at the same time.')
            );
        }

        if (!empty($args['id'])) {
            return $this->termDataProvider->getDataById(
                (int)$args['id'],
                $info->getFieldSelection()
            );
        } elseif (!empty($args['slug'])) {
            return $this->termDataProvider->getDataBySlug(
                $args['slug'],
                $info->getFieldSelection()
            );
        } else {
            throw new GraphQlInputException(
                __('You must specify either an id or slug filter.')
            );
        }
    }
}
