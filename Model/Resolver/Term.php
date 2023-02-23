<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

use FishPig\WordPress\Model\TermRepository;
use FishPig\WordPressGraphQl\Model\DataProvider\Term as TermDataProvider;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class Term implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var TermRepository
     */
    private $termRepository = null;

    /**
     * @var TermDataProvider
     */
    private $termDataProvider = null;

    /**
     *
     */
    public function __construct(
        TermRepository $termRepository,
        TermDataProvider $termDataProvider
    ) {
        $this->termRepository = $termRepository;
        $this->termDataProvider = $termDataProvider;
    }

    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!empty($args['withTaxonomy'])) {
            try {
                $term = $this->termRepository->getWithTaxonomy(
                    (int)$args['id'],
                    $args['withTaxonomy']
                );

                return $this->termDataProvider->getData(
                    $term,
                    $info->getFieldSelection()
                );
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                throw new GraphQlNoSuchEntityException(__($e->getMessage()));
            }
        }

        return $this->termDataProvider->getDataById(
            (int)$args['id'],
            $info->getFieldSelection()
        );
    }
}
