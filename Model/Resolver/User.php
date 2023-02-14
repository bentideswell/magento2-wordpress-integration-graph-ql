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

class User implements ResolverInterface
{
    /**
     * @var \FishPig\WordPressGraphQl\Model\DataProvider\User
     */
    private $dataProvider = null;

    /**
     *
     */
    public function __construct(
        \FishPig\WordPressGraphQl\Model\DataProvider\User $dataProvider
    ) {
        $this->dataProvider = $dataProvider;
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
        return $this->dataProvider->getDataById((int)$args['id']);
    }
}
