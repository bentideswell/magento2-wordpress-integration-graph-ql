<?php
/**
 * @package FishPig_WordPress
 * @author  Ben Tideswell (ben@fishpig.com)
 * @url     https://fishpig.co.uk/magento/wordpress-integration/
 */
declare(strict_types=1);

namespace FishPig\WordPressGraphQl\App\Debug\Tests\GraphQl;

use FishPig\WordPress\Model\Post as PostModel;
use FishPig\WordPress\Model\Term as TermModel;
use FishPig\WordPress\Model\User as UserModel;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

abstract class AbstractTests implements \FishPig\WordPress\App\Debug\TestInterface
{
    /**
     * @auto
     */
    protected $urlBuilder = null;

    /**
     * @auto
     */
    protected $postCollectionFactory = null;

    /**
     * @auto
     */
    protected $termCollectionFactory = null;

    /**
     * @auto
     */
    protected $userCollectionFactory = null;

    /**
     * @auto
     */
    protected $commentCollectionFactory = null;

    /**
     * @auto
     */
    protected $context = null;

    /**
     * @auto
     */
    protected $graphQlConfig = null;

    /**
     *
     */
    const TAB = '    ';

    /**
     *
     */
    const EXPECT_SOME_RESULTS = -1;
    const EXPECT_NO_SUCH_ENTITY = -2;

    /**
     *
     */
    public function __construct(
        \FishPig\WordPressGraphQl\App\Debug\Tests\GraphQl\Context $context,
        \Magento\Framework\GraphQl\Config $graphQlConfig
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->postCollectionFactory = $context->getPostCollectionFactory();
        $this->termCollectionFactory = $context->getTermCollectionFactory();
        $this->userCollectionFactory = $context->getUserCollectionFactory();
        $this->commentCollectionFactory = $context->getCommentCollectionFactory();
        $this->graphQlConfig = $context->getGraphQlConfig();
    }

    /**
     *
     */
    protected function doQueryAndValidate(
        string $name,
        array $filters = [],
        array $fields = [],
        ?int $expectedCount = null
    ): ?array {
        try {
            $results = $this->doQuery($name, $filters, $fields);
        } catch (GraphQlNoSuchEntityException $e) {
            if ($expectedCount !== self::EXPECT_NO_SUCH_ENTITY) {
                throw $e;
            }
            return null;
        }

        if ($expectedCount !== null) {
            if (isset($results['data'][$name]['items'])) {
                $actualCount = count($results['data'][$name]['items']);
                // An Expected Count of -1 means just anything above 0
                $hasCountError = ($expectedCount === self::EXPECT_SOME_RESULTS && $actualCount === 0)
                                 || ($expectedCount >= 0 && $expectedCount !== $actualCount);

                if ($hasCountError) {
                    throw new \ArithmeticError(
                        sprintf(
                            'Expected %d results but found %d results usings filters (%s). Data was: %s',
                            $expectedCount,
                            count($results['data'][$name]['items']),
                            urldecode(http_build_query($filters)),
                            "\n\n" . print_r($results, true)
                        )
                    );
                }
            }
        }

        return $results;
    }

    /**
     *
     */
    protected function doPaginationQuery(
        string $name,
        array $filters = [],
        array $fields = []
    ): void {

        $initialPageSize = 1;
        $data = $this->doQueryAndValidate(
            $name,
            array_merge($filters, ['pageSize' => $initialPageSize]),
            $fields,
            $initialPageSize
        );

        // Lets test as many values as we can
        $totalCount = $data['data'][$name]['total_count'];
        $totalPages = $data['data'][$name]['page_info']['total_pages'];
        $pageSize = $data['data'][$name]['page_info']['page_size'];
        $currentPage = $data['data'][$name]['page_info']['current_page'];

        $comparisons = [
            'pageSize' => [$initialPageSize, $pageSize],
            'totalPages' => [ceil($totalCount/$pageSize), $totalPages]
        ];

        foreach ($comparisons as $field => $values) {
            if ((int)$values[0] !== (int)$values[1]) {
                throw new \Exception(
                    sprintf(
                        'Expected %s=%d but found %s=%d.',
                        $field,
                        $values[0],
                        $field,
                        $values[1]
                    )
                );
            }
        }

        // Now we are going to find how many is half plus 1 and request
        // page 2. This should be total - this value. We should then request
        // page 3 and it should be empty
        $justOverHalfCount = (int)ceil($totalCount/2)+1;
        $expectedCount = $totalCount - $justOverHalfCount;
        $this->doQueryAndValidate(
            $name,
            array_merge(
                $filters,
                [
                    'pageSize' => $justOverHalfCount,
                    'currentPage' => 2
                ]
            ),
            $fields,
            $expectedCount
        );
        // Now request page 3 and it should be empty
        $this->doQueryAndValidate(
            $name,
            array_merge(
                $filters,
                [
                    'pageSize' => $justOverHalfCount,
                    'currentPage' => 3
                ]
            ),
            $fields,
            0
        );
    }

    /**
     * @return void
     */
    protected function doQuery(string $name, array $filters = [], array $fields = []): array
    {
        // Build Query
        $query = str_replace(
            "\t",
            self::TAB,
            sprintf("query {\n\t%s %s %s\n}",
                $name,
                $this->renderFilters($filters),
                $this->renderFields($fields)
            )
        );

        // Build HTTP request
        curl_setopt_array(
            $ch = curl_init(),
            [
                CURLOPT_URL => $this->getGraphQlEndpoint(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => json_encode(['query' => $query]),
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false
            ]
        );

        $result = curl_exec($ch);

        if ($errorNo = curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException(
                sprintf("Error %d: %s", $errorNo, $error)
            );
        }


        $data = json_decode($result, true, JSON_THROW_ON_ERROR);

        if (!empty($data['errors'])) {
            // Handle no such entity exceptions
            if (isset($data['errors'][0]['extensions']['category'])
                && $data['errors'][0]['extensions']['category'] === 'graphql-no-such-entity') {
                throw new \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException(
                    __($data['errors'][0]['message'])
                );
            }

            $errors = [];
            foreach ($data['errors'] as $error) {
                if (!empty($error['message'])) {
                    $errors[] = $error['debugMessage'] ?? $error['message'];
                } else {
                    print_r($error);
                    exit;
                }
            }

            throw new \RuntimeException(
                sprintf(
                    "%d GraphQL Errors\n\n%s",
                    count($errors),
                    self::TAB . implode("\n" . self::TAB, $errors)
                )
            );
        }

        if (!isset($data['data'][$name])) {
            throw new \RuntimeException(
                sprintf(
                    'Data key not found using data/' . $name
                )
            );
        }

        return $data;
    }

    /**
     *
     */
    protected function getGraphQlEndpoint(): string
    {
        return $this->urlBuilder->getUrl('', ['_direct' => 'graphql']);
    }

    /**
     *
     */
    private function renderFilters(array $filters): string
    {
        if ($filters) {
            $output = [];
            foreach ($filters as $key => $value) {
                $output[] = $key . ': ' . $this->renderFilterValue($value);
            }
            return "(\n\t\t" . implode("\n\t\t", $output) . "\n\t) ";
        }
        return '';
    }

    /**
     *
     */
    private function renderFilterValue($value): string
    {
        if (is_array($value)) {
            foreach ($value as $index => $v) {
                $value[$index] = $this->renderFilterValue($v);
            }

            return '[' . implode(', ', $value) . ']';
        } elseif (is_int($value) || is_float($value) || is_double($value)) {
            return (string)$value;
        } elseif (is_string($value)) {
            return '"' . $value . '"';
        }
    }

    /**
     *
     */
    private function renderFields(array $fields, $level = 1): string
    {
        $tab = str_repeat("\t", $level);
        $tab2 = str_repeat("\t", $level+1);

        $output = [];

        foreach ($fields as $index => $value) {
            if (is_array($value)) {
                $output[] = sprintf("%s %s", $index, $this->renderFields($value, $level+1));
            } else {
                $output[] = $value;
            }
        }

        return "{\n" . $tab2 . implode("\n" . $tab2, $output) . "\n" . $tab . "}";
    }

    /**
     *
     */
    protected function getPost(array $filters = []): ?PostModel
    {
        $posts = $this->postCollectionFactory->create()
            ->addIsViewableFilter()
            ->setPageSize(1);

        foreach ($filters as $filter => $value) {
            if ($filter === 'post_type') {
                $posts->addPostTypeFilter($value);
            }
        }

        return count($posts) > 0 ? $posts->getFirstItem() : null;
    }

    /**
     *
     */
    protected function getTerm(array $filters = []): ?TermModel
    {
        $terms = $this->termCollectionFactory->create()
            ->setPageSize(1);

        return count($terms) > 0 ? $terms->getFirstItem() : null;
    }

    /**
     *
     */
    protected function getUser(array $filters = []): ?UserModel
    {
        $users = $this->userCollectionFactory->create()
            ->setPageSize(1);

        return count($users) > 0 ? $users->getFirstItem() : null;
    }

    /**
     *
     */
    protected function getGraphQlType(string $type): ?\Magento\Framework\GraphQl\Config\Element\Type
    {
        if ($element = $this->graphQlConfig->getConfigElement($type)) {
            if ($element instanceof \Magento\Framework\GraphQl\Config\Element\Type) {
                return $element;
            }
        }

        return null;
    }

    /**
     *
     */
    protected function buildFieldsFromType(string $type, $safety = 10)
    {
        if ($safety === 0) {
            return [];
        }
        $fields = [];

        if ($typeInstance = $this->getGraphQlType($type)) {
            foreach ($typeInstance->getFields() as $field) {
                if (in_array($field->getTypeName(), ['Int', 'String', 'ID'])) {
                    // Move these fields to the top
                    $fields = array_merge(
                        [$field->getName()],
                        $fields
                    );
                } elseif ($this->getGraphQlType($field->getTypeName())) {
                    $fields[$field->getName()] = $this->buildFieldsFromType($field->getTypeName(), $safety-1);
                }
            }
        }

        return $fields;
    }
}
