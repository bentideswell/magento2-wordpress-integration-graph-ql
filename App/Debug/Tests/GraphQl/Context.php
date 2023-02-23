<?php
/**
 * @package FishPig_WordPress
 * @author  Ben Tideswell (ben@fishpig.com)
 * @url     https://fishpig.co.uk/magento/wordpress-integration/
 */
declare(strict_types=1);

namespace FishPig\WordPressGraphQl\App\Debug\Tests\GraphQl;

use Magento\Framework\UrlInterface;
use FishPig\WordPress\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;
use FishPig\WordPress\Model\ResourceModel\Term\CollectionFactory as TermCollectionFactory;
use FishPig\WordPress\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use FishPig\WordPress\Model\ResourceModel\Post\Comment\CollectionFactory as CommentCollectionFactory;
use Magento\Framework\GraphQl\Config as GraphQlConfig;

class Context
{
    /**
     *
     */
    public function __construct(
        PostCollectionFactory $postCollectionFactory,
        TermCollectionFactory $termCollectionFactory,
        UserCollectionFactory $userCollectionFactory,
        CommentCollectionFactory $commentCollectionFactory,
        UrlInterface $urlBuilder,
        GraphQlConfig $graphQlConfig

    ) {
        $this->postCollectionFactory = $postCollectionFactory;
        $this->termCollectionFactory = $termCollectionFactory;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->commentCollectionFactory = $commentCollectionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->graphQlConfig = $graphQlConfig;
    }

    /**
     * @return GraphQlConfig
     */
    public function getGraphQlConfig(): GraphQlConfig
    {
        return $this->graphQlConfig;
    }

    /**
     * @return PostCollectionFactory
     */
    public function getPostCollectionFactory(): PostCollectionFactory
    {
        return $this->postCollectionFactory;
    }

    /**
     * @return TermCollectionFactory
     */
    public function getTermCollectionFactory(): TermCollectionFactory
    {
        return $this->termCollectionFactory;
    }

    /**
     * @return UserCollectionFactory
     */
    public function getUserCollectionFactory(): UserCollectionFactory
    {
        return $this->userCollectionFactory;
    }

    /**
     * @return CommentCollectionFactory
     */
    public function getCommentCollectionFactory(): CommentCollectionFactory
    {
        return $this->commentCollectionFactory;
    }

    /**
     * @return UrlInterface
     */
    public function getUrlBuilder(): UrlInterface
    {
        return $this->urlBuilder;
    }
}
