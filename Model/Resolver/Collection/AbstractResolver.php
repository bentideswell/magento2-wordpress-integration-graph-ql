<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\Resolver\Collection;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     *
     */
    abstract protected function validateInput(array $args): array;

    /**
     *
     */
    abstract protected function buildCollection(array $args): iterable;

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
        $args = $this->validateInput($args);

        $collection = $this->buildCollection($args);

        // Apply page_info args
        if (!empty($args['pageSize'])) {
            $collection->setPageSize((int)$args['pageSize']);
        }

        if (!empty($args['currentPage'])) {
            $collection->setCurPage((int)$args['currentPage']);
        }

        return [
            'total_count' => $collection->getSize(),
            'items' => array_map(
                function ($item) use ($args) {
                    return $this->prepareItemData($item, $args);
                },
                $collection->getItems()
            ),
            'page_info' => [
                'page_size' => $collection->getPageSize(),
                'current_page' => $collection->getCurPage(),
                'total_pages' => $collection->getLastPageNumber()
            ],
        ];
    }

    /**
     *
     */
    protected function prepareItemData(object $item, array $args = []): iterable
    {
        return $item->getData();
    }
}
