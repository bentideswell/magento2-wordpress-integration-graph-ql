<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\DataProvider;

use FishPig\WordPress\Model\Post as PostModel;

class Post
{
    /**
     *
     */
    private $postRepository = null;

    /**
     *
     */
    private $userDataProvider = null;

    /**
     *
     */
    private $termDataProvider = null;

    /**
     *
     */
    public function __construct(
        \FishPig\WordPress\Model\PostRepository $postRepository,
        \FishPig\WordPressGraphQl\Model\DataProvider\User $userDataProvider,
        \FishPig\WordPressGraphQl\Model\DataProvider\Term $termDataProvider
    ) {
        $this->postRepository = $postRepository;
        $this->userDataProvider = $userDataProvider;
        $this->termDataProvider = $termDataProvider;
    }

    /**
     *
     */
    public function getDataById($id, array $fields = null): array
    {
        try {
            return $this->getData(
                $this->postRepository->get((int)$id),
                $fields
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [];
        }
    }
    /**
     *
     */
    public function getData(PostModel $post, array $fields = null): array
    {
        $data = [
            'model' => $post,
            'id' => (int)$post->getId(),
            'post_title' => $post->getName() ?? '',
            'post_date' => $post->getData('post_date_gmt'),
            'post_status' => $post->getData('post_status'),
            'comment_status' => $post->getData('comment_status'),
            'post_name' => $post->getData('post_name'),
            'url' => $post->getUrl(),
            'post_parent' => (int)$post->getData('post_parent'),
            'post_type' => $post->getPostType(),
            'comment_count' => (int)$post->getData('comment_count'),
        ];

        // Determine whether to use include fields, even the lazy ones
        $includeAllFields = $fields === null || count($fields) === 0;

        // These calls can take up time, so if the user does not want
        // this field, lets not call it
        foreach ($this->getLazyFields() as $field => $getter) {
            if ($includeAllFields || isset($fields[$field])) {
                $data[$field] = $getter($post, $fields[$field] ?? 1);
            }
        }

        return $data;
    }

    /**
     *
     */
    private function getLazyFields(): array
    {
        return [
            'post_excerpt' => function($post, $value) {
                return (int)$value === 1 ? $post->getExcerpt() : '';
            },
            'post_content' => function($post, $value) {
                return (int)$value === 1 ? $post->getContent() : '';
            },
            'user' => function($post, $value) {
                return (int)$value === 1 ? $this->userDataProvider->getDataById(
                    $post->getUserId()
                ) : '';
            }
        ];
    }
}
