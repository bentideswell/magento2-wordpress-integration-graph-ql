<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\DataProvider\Post;

use FishPig\WordPress\Model\Post\Comment as CommentModel;

class Comment
{
    /**
     *
     */
    public function getData(CommentModel $comment, array $fields = null): array
    {
        $data = [
            'id' => (int)$comment->getId(),
            'post_id' => (int)$comment->getPostId(),
            'author_name' => $comment->getCommentAuthor(),
            'author_url' => $comment->getCommentAuthorUrl(),
            'comment_date' => $comment->getCommentDate(),
            'url' => $comment->getUrl(),
            'content' => $comment->getCommentContent(),
            'comment_parent' => (int)$comment->getData('comment_parent'),
        ];

        return $data;
    }
}
