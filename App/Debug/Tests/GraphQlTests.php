<?php
/**
 * @package FishPig_WordPress
 * @author  Ben Tideswell (ben@fishpig.com)
 * @url     https://fishpig.co.uk/magento/wordpress-integration/
 */
declare(strict_types=1);

namespace FishPig\WordPressGraphQl\App\Debug\Tests;

use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use FishPig\WordPressGraphQl\App\Debug\Tests\GraphQl\AbstractTests;

class GraphQlTests extends AbstractTests
{


    /**
     * @return void
     */
    public function run(array $options = []): void
    {
        $this->doPostTests();
        $this->doTermTests();
        $this->doUserTests();
        $this->doCommentTests();
    }

    /**
     *
     */
    private function doPostTests(): void
    {
        if (null === ($post = $this->getPost(['post_type' => 'post']))) {
            throw new \RuntimeException('Cannot find a post to do tests.');
        }

        if (null === ($term = $this->getTerm())) {
            throw new \RuntimeException('Cannot find a term to do tests.');
        }

        if (null === ($user = $this->getUser())) {
            throw new \RuntimeException('Cannot find a term to do tests.');
        }

        $fields = $this->buildFieldsFromType('WordPressPostSearchResult');

        // Get a post with all of the fields by a specific ID
        // Also load the taxonomy data
        $this->doQueryAndValidate(
            'wpPosts',
            [
                'id' => $post->getId(),
                'withTaxonomies' => ['category', 'post_tag']
            ],
            $fields,
            1
        );

        // Now get the same post by permalink
        // This is the relative URL for the post
        $this->doQueryAndValidate(
            'wpPosts',
            ['permalink' => $post->getData('permalink')],
            $fields,
            1
        );

        // Now test with pageSize
        $pageSize = 10;
        $results = $this->doQueryAndValidate(
            'wpPosts',
            [
                'post_type' => 'post',
                'pageSize' => $pageSize
            ],
            $fields
        );

        if (count($results['data']['wpPosts']['items']) > $pageSize) {
            throw new \Exception(
                sprintf(
                    'Expected pageSize=%s but found pageSize=%s',
                    $pageSize,
                    count($results['data']['wpPosts']['items'])
                )
            );
        }

        // Use Term filter
        $expectedCount = count($term->getPostCollection()->addIsViewableFilter());
        $this->doQueryAndValidate(
            'wpPosts',
            [
                'term_id' => $term->getId(),
                'term_taxonomy' => $term->getTaxonomy(),
                'post_type' => ['post', 'page'],
                'pageSize' => $expectedCount*2
            ],
            $fields,
            $expectedCount
        );

        // Use the User ID filter
        $expectedCount = count($user->getPostCollection()->addIsViewableFilter());
        $this->doQueryAndValidate(
            'wpPosts',
            [
                'user_id' => $user->getId(),
                'post_type' => ['post', 'page'],
                'pageSize' => $expectedCount*2
            ],
            $fields,
            $expectedCount
        );

        // Try pagination
        $this->doPaginationQuery('wpPosts', [], $fields);
    }

    /**
     *
     */
    private function doTermTests(): void
    {
        if (null === ($term = $this->getTerm())) {
            throw new \RuntimeException('Cannot find a term to do tests.');
        }

        $fields = $this->buildFieldsFromType('WordPressTerm');

        // Load a term by ID
        $this->doQueryAndValidate(
            'wpTerm',
            ['id' => $term->getId()],
            $fields,
            1
        );

        // Now load the same term with the taxonomy set.
        // This is the right taxonomy so should return 1 term
        $data = $this->doQueryAndValidate(
            'wpTerm',
            [
                'id' => $term->getId(),
                'withTaxonomy' => $term->getTaxonomy()
            ],
            $fields,
            1
        );

        // Now test with the wrong taxonomy. This should throw an exception
        // This method will expect and catch the exception because we pass
        // AbstractTests::EXPECT_NO_SUCH_ENTITY
        $data = $this->doQueryAndValidate(
            'wpTerm',
            [
                'id' => $term->getId(),
                'withTaxonomy' => 'post_tag'
            ],
            $fields,
            AbstractTests::EXPECT_NO_SUCH_ENTITY
        );
    }

    /**
     *
     */
    private function doUserTests(): void
    {
        if (null === ($user = $this->getUser())) {
            throw new \RuntimeException('Cannot find a user to do tests.');
        }

        $fields = $this->buildFieldsFromType('WordPressUser');

        // Load a user by ID
        $this->doQueryAndValidate(
            'wpUser',
            ['id' => $user->getId()],
            $fields,
            1
        );

        // Load a user by nicename
        $this->doQueryAndValidate(
            'wpUser',
            ['nicename' => $user->getUserNicename()],
            $fields,
            1
        );
    }

    /**
     *
     */
    private function doCommentTests(): void
    {
        // Get Posts
        $posts = $this->postCollectionFactory->create()
            ->addIsViewableFilter()
            ->addFieldToFilter('comment_count', ['gt' => 0]);
        // Remove existing orders
        $posts->getSelect()->reset(\Zend_Db_Select::ORDER);
        // Nw order by post with most comments
        $posts->getSelect()->order('comment_count DESC');
        // We only need 1
        $posts->setPageSize(1);

        $posts->load();

        if (count($posts) === 0) {
            throw new \Exception(
                'There are no comments in the system. Unable to run tests'
            );
        }

        $fields = $this->buildFieldsFromType('WordPressPostComments');

        $post = $posts->getFirstItem();

        // Calculate total comments for post
        $totalComments = count(
            $this->commentCollectionFactory->create()
                ->setPost($post)
                ->addCommentApprovedFilter()
        );

        $this->doPaginationQuery(
            'wpComments',
            ['post_id' => $post->getId()],
            $fields
        );
    }
}
